<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = Subscription::with('tenant')
            ->when($request->tenant_id, fn($q) => $q->where('tenant_id', $request->tenant_id))
            ->latest()
            ->paginate(15);

        return response()->json($subscriptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $subscription = Subscription::create([...$validated, 'status' => 'active']);

        // Update tenant status to active
        Tenant::findOrFail($validated['tenant_id'])->update(['status' => 'active']);

        return response()->json($subscription->load('tenant'), 201);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'ends_at' => 'sometimes|date',
            'status' => 'sometimes|in:active,expired,cancelled',
            'notes' => 'nullable|string',
        ]);

        $subscription->update($validated);
        return response()->json($subscription);
    }
}
