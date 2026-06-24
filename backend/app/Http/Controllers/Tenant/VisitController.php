<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Visit;
use App\Models\VisitInventory;
use App\Models\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $visits = Visit::with(['patient', 'doctor', 'doctor.user'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->doctor_id, fn($q) => $q->where('doctor_id', $request->doctor_id))
            ->when($request->is_paid !== null, fn($q) => $q->where('is_paid', $request->boolean('is_paid')))
            ->when($request->from, fn($q) => $q->whereDate('visited_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('visited_at', '<=', $request->to))
            ->latest('visited_at')
            ->paginate($request->per_page ?? 15);

        return response()->json($visits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'visited_at' => 'nullable|date',
            'diagnosis' => 'nullable|string',
            'prescription' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,insurance',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
            'services.*.price' => 'required|numeric|min:0',
            'inventory' => 'nullable|array',
            'inventory.*.item_id' => 'required|exists:inventory_items,id',
            'inventory.*.quantity_used' => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $totalAmount = collect($validated['services'])->sum(fn($s) => $s['price'] * $s['quantity']);
            $discount = $validated['discount'] ?? 0;

            // Pre-load inventory items to calculate total and avoid N+1
            $inventoryItems = collect($validated['inventory'] ?? [])->keyBy('item_id')->map(
                fn($inv) => ['inv' => $inv, 'item' => InventoryItem::find($inv['item_id'])]
            );

            foreach ($inventoryItems as $entry) {
                $totalAmount += ($entry['item']->price_per_unit ?? 0) * $entry['inv']['quantity_used'];
            }

            $visit = Visit::create([
                'patient_id'     => $validated['patient_id'],
                'doctor_id'      => $validated['doctor_id'],
                'appointment_id' => $validated['appointment_id'] ?? null,
                'visited_at'     => $validated['visited_at'] ?? now(),
                'diagnosis'      => $validated['diagnosis'] ?? null,
                'prescription'   => $validated['prescription'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'total_amount'   => $totalAmount,
                'discount'       => $discount,
                'paid_amount'    => 0,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'is_paid'        => false,
            ]);

            foreach ($validated['services'] as $svc) {
                VisitService::create([
                    'visit_id'   => $visit->id,
                    'service_id' => $svc['service_id'],
                    'quantity'   => $svc['quantity'],
                    'price'      => $svc['price'],
                    'total'      => $svc['price'] * $svc['quantity'],
                ]);
            }

            foreach ($inventoryItems as $entry) {
                $inv  = $entry['inv'];
                $item = $entry['item'];
                $unitPrice  = (float) ($item->price_per_unit ?? 0);
                $totalPrice = $unitPrice * $inv['quantity_used'];

                VisitInventory::create([
                    'visit_id'     => $visit->id,
                    'item_id'      => $inv['item_id'],
                    'quantity_used' => $inv['quantity_used'],
                    'unit_price'   => $unitPrice,
                    'total_price'  => $totalPrice,
                ]);

                $item->decrement('quantity', $inv['quantity_used']);
                InventoryTransaction::create([
                    'item_id'        => $inv['item_id'],
                    'type'           => 'out',
                    'quantity'       => $inv['quantity_used'],
                    'reference_type' => Visit::class,
                    'reference_id'   => $visit->id,
                    'performed_by'   => auth()->id(),
                ]);

                $remaining = (float) $item->fresh()->quantity;
                if ($remaining <= (float) $item->min_quantity) {
                    Notification::create([
                        'type'  => 'low_stock',
                        'title' => 'Inventar kam qoldi',
                        'body'  => "{$item->name}: {$remaining} {$item->unit} qoldi (min: {$item->min_quantity})",
                        'data'  => ['item_id' => $item->id],
                    ]);
                }
            }

            if ($request->appointment_id) {
                \App\Models\Appointment::find($request->appointment_id)
                    ?->update(['status' => 'in_progress']);
            }

            $loaded = $visit->load(['patient', 'doctor.user', 'services.service', 'inventory.item']);

            return response()->json($loaded, 201);
        });
    }

    public function show(Visit $visit)
    {
        return response()->json($visit->load(['patient', 'doctor.user', 'services.service', 'inventory.item', 'receipt']));
    }

    public function update(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'diagnosis' => 'nullable|string',
            'prescription' => 'nullable|string',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $visit->update($validated);
        return response()->json($visit->load(['patient', 'services.service']));
    }

    private function dispatchPaymentNotification(Visit $visit): void
    {
        $visit->loadMissing(['patient', 'doctor.user', 'services.service', 'inventory.item']);
        $doctor = $visit->doctor;

        $doctorLine = $doctor
            ? 'Dr. ' . $doctor->user->name . ($doctor->specialization ? " ({$doctor->specialization})" : '')
            : '—';

        $paymentMethod = match ($visit->payment_method) {
            'cash'      => '💵 Naqd',
            'card'      => '💳 Karta',
            'insurance' => '🏥 Sug\'urta',
            default     => ucfirst((string) $visit->payment_method),
        };

        $serviceLines = $visit->services->map(
            fn($vs) => "  • {$vs->service->name}" . ($vs->quantity > 1 ? " ×{$vs->quantity}" : '') . ' — ' . number_format((float) $vs->total, 0, '.', ' ') . " so'm"
        )->implode("\n");

        $inventoryLines = '';
        if ($visit->inventory && $visit->inventory->count() > 0) {
            $lines = $visit->inventory->map(function ($inv) {
                $line = "  • {$inv->item->name} ×{$inv->quantity_used} {$inv->item->unit}";
                if ((float) $inv->total_price > 0) {
                    $line .= ' — ' . number_format((float) $inv->total_price, 0, '.', ' ') . " so'm";
                }
                return $line;
            })->implode("\n");
            $inventoryLines = "\n🧪 <b>Materiallar:</b>\n{$lines}\n";
        }

        $message  = "🏥 <b>EverMED — TO'LOV QABUL QILINDI</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "👤 <b>Bemor:</b> {$visit->patient->full_name}\n";
        if ($visit->patient->phone) {
            $message .= "📞 <b>Tel:</b> {$visit->patient->phone}\n";
        }
        $message .= "👨‍⚕️ <b>Shifokor:</b> {$doctorLine}\n";
        $message .= "📅 <b>Sana:</b> " . $visit->visited_at->format('d.m.Y H:i') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🩺 <b>Xizmatlar:</b>\n{$serviceLines}\n";
        $message .= $inventoryLines;
        if ((float) $visit->discount > 0) {
            $message .= "🎁 <b>Chegirma:</b> −" . number_format((float) $visit->discount, 0, '.', ' ') . " so'm\n";
        }
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 <b>Jami to'landi:</b> " . number_format((float) $visit->paid_amount, 0, '.', ' ') . " so'm\n";
        $message .= "💳 <b>To'lov usuli:</b> {$paymentMethod}\n";

        SendTelegramNotification::dispatch(tenant('id'), $message);
    }

    public function pay(Request $request, Visit $visit)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,insurance',
        ]);

        $wasAlreadyPaid = $visit->is_paid;

        $visit->update([
            'paid_amount' => $request->paid_amount,
            'payment_method' => $request->payment_method,
            'is_paid' => $request->paid_amount >= ($visit->total_amount - $visit->discount),
        ]);

        if ($visit->appointment_id) {
            $visit->appointment?->update(['status' => 'completed']);
        }

        $updated = $visit->fresh();

        if (!$wasAlreadyPaid && $updated->is_paid) {
            $this->dispatchPaymentNotification($updated);
        }

        return response()->json($updated);
    }
}
