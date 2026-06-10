<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();

        $monthlyRevenue = Subscription::where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $newThisMonth = Tenant::whereMonth('created_at', now()->month)->count();

        $recentTenants = Tenant::with('activeSubscription')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_tenants' => $totalTenants,
                'active' => $activeTenants,
                'trial' => $trialTenants,
                'suspended' => $suspendedTenants,
                'monthly_revenue' => $monthlyRevenue,
                'new_this_month' => $newThisMonth,
            ],
            'recent_tenants' => $recentTenants,
        ]);
    }
}
