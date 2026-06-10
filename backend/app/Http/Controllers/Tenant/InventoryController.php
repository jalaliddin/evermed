<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $items = InventoryItem::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->status === 'low', fn($q) => $q->whereColumn('quantity', '<=', DB::raw('min_quantity * 1.5')))
            ->when($request->status === 'critical', fn($q) => $q->whereColumn('quantity', '<=', 'min_quantity'))
            ->paginate($request->per_page ?? 20);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'unit' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return response()->json(InventoryItem::create($validated), 201);
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string',
            'unit' => 'nullable|string',
            'min_quantity' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $inventory->update($validated);
        return response()->json($inventory);
    }

    public function stockIn(Request $request, InventoryItem $inventory)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $inventory->increment('quantity', $request->quantity);
        InventoryTransaction::create([
            'item_id' => $inventory->id,
            'type' => 'in',
            'quantity' => $request->quantity,
            'performed_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return response()->json($inventory->fresh());
    }

    public function stockOut(Request $request, InventoryItem $inventory)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $inventory->decrement('quantity', $request->quantity);
        InventoryTransaction::create([
            'item_id' => $inventory->id,
            'type' => 'out',
            'quantity' => $request->quantity,
            'performed_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        $fresh = $inventory->fresh();
        if ($fresh->quantity <= $fresh->min_quantity) {
            Notification::create([
                'type' => 'low_stock',
                'title' => 'Inventar kam qoldi',
                'body' => "{$fresh->name}: {$fresh->quantity} {$fresh->unit} qoldi",
                'data' => ['item_id' => $fresh->id],
            ]);
        }

        return response()->json($fresh);
    }

    public function transactions(Request $request)
    {
        $transactions = InventoryTransaction::with(['item', 'performer'])
            ->when($request->item_id, fn($q) => $q->where('item_id', $request->item_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    public function lowStock()
    {
        return response()->json(
            InventoryItem::whereColumn('quantity', '<=', \DB::raw('min_quantity * 1.5'))->get()
        );
    }
}
