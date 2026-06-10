<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with('activeSubscription')
            ->paginate(15);

        return response()->json($tenants);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);

        $tenant = Tenant::create([
            ...$validated,
            'id' => $validated['slug'],
            'status' => 'trial',
        ]);

        $tenant->createDomain(['domain' => $validated['slug'] . '.med.eversoft.uz']);

        // Create tenant database
        $tenant->run(function () {
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });

        return response()->json($tenant, 201);
    }

    public function show(string $id)
    {
        $tenant = Tenant::with(['subscriptions'])->findOrFail($id);
        return response()->json($tenant);
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,trial,suspended',
            'plan' => 'nullable|string',
        ]);

        $tenant->update($validated);
        return response()->json($tenant);
    }

    public function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
