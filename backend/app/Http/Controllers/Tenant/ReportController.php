<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function financial(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $totalRevenue = Visit::whereBetween('visited_at', [$from, $to])->where('is_paid', true)->sum('paid_amount');
        $totalPatients = Visit::whereBetween('visited_at', [$from, $to])->distinct('patient_id')->count('patient_id');
        $totalDiscount = Visit::whereBetween('visited_at', [$from, $to])->sum('discount');

        $daily = Visit::where('is_paid', true)
            ->whereBetween('visited_at', [$from, $to])
            ->selectRaw('DATE(visited_at) as date, COUNT(DISTINCT patient_id) as patients, SUM(paid_amount) as revenue, SUM(discount) as discount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $maxDay = $daily->sortByDesc('revenue')->first();

        return response()->json([
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_patients' => $totalPatients,
                'total_discount' => $totalDiscount,
                'avg_per_day' => $daily->count() > 0 ? round($totalRevenue / $daily->count()) : 0,
                'max_day' => $maxDay,
            ],
            'daily' => $daily,
        ]);
    }

    public function doctors(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $doctors = Doctor::with('user')
            ->when($request->doctor_id, fn($q) => $q->where('id', $request->doctor_id))
            ->withCount(['visits as visits_count' => fn($q) => $q->whereBetween('visited_at', [$from, $to])])
            ->withSum(['visits as revenue' => fn($q) => $q->whereBetween('visited_at', [$from, $to])->where('is_paid', true)], 'paid_amount')
            ->get()
            ->map(fn($d) => [
                'doctor' => $d,
                'visits_count' => $d->visits_count,
                'revenue' => $d->revenue ?? 0,
                'avg_check' => $d->visits_count > 0 ? round(($d->revenue ?? 0) / $d->visits_count) : 0,
            ]);

        return response()->json(compact('doctors'));
    }

    public function services(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $services = DB::table('visit_services')
            ->join('services', 'visit_services.service_id', '=', 'services.id')
            ->join('visits', 'visit_services.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->selectRaw('services.name, COUNT(*) as count, SUM(visit_services.total) as revenue')
            ->groupBy('services.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $categories = DB::table('visit_services')
            ->join('services', 'visit_services.service_id', '=', 'services.id')
            ->join('service_categories', 'services.category_id', '=', 'service_categories.id')
            ->join('visits', 'visit_services.visit_id', '=', 'visits.id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->selectRaw('service_categories.name, SUM(visit_services.total) as revenue')
            ->groupBy('service_categories.name')
            ->orderByDesc('revenue')
            ->get();

        return response()->json(compact('services', 'categories'));
    }

    public function inventory(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $transactions = InventoryTransaction::with(['item', 'performer'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(20);

        $topUsed = InventoryTransaction::where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->join('inventory_items', 'inventory_transactions.item_id', '=', 'inventory_items.id')
            ->selectRaw('inventory_items.name, SUM(inventory_transactions.quantity) as total_used')
            ->groupBy('inventory_items.name')
            ->orderByDesc('total_used')
            ->limit(10)
            ->get();

        return response()->json(compact('transactions', 'topUsed'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:financial,doctors,services,inventory',
            'format' => 'required|in:excel,pdf',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
        ]);

        $data = $this->{$request->type}($request)->getData(true);

        // Flatten paginated inventory transactions for export
        if ($request->type === 'inventory' && isset($data['transactions']['data'])) {
            $data['transactions'] = $data['transactions']['data'];
        }

        $filename = "hisobot_{$request->type}_" . now()->format('Y-m-d');

        if ($request->format === 'excel') {
            return Excel::download(
                new \App\Exports\ReportExport($request->type, $data),
                "{$filename}.xlsx"
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.' . $request->type, $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }
}
