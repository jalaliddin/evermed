<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        $todayPatients = Visit::whereDate('visited_at', $today)->distinct('patient_id')->count('patient_id');
        $todayAppointments = Appointment::whereDate('scheduled_at', $today)->count();
        $todayRevenue = Visit::whereDate('visited_at', $today)->where('is_paid', true)->sum('paid_amount');

        $lowStockCount = InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->count();

        // Last 7 days revenue
        $revenueChart = Visit::where('is_paid', true)
            ->where('visited_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(visited_at) as date, SUM(paid_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Services breakdown
        $servicesChart = DB::table('visit_services')
            ->join('services', 'visit_services.service_id', '=', 'services.id')
            ->selectRaw('services.name, SUM(visit_services.total) as total')
            ->groupBy('services.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Today's appointments
        $todayAppointmentsList = Appointment::with(['patient', 'doctor.user', 'service'])
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        // Recent notifications
        $notifications = \App\Models\Notification::where('is_read', false)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'today_patients' => $todayPatients,
                'today_appointments' => $todayAppointments,
                'today_revenue' => $todayRevenue,
                'low_stock_count' => $lowStockCount,
            ],
            'revenue_chart' => $revenueChart,
            'services_chart' => $servicesChart,
            'today_appointments' => $todayAppointmentsList,
            'notifications' => $notifications,
        ]);
    }
}
