<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
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
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'plan'           => 'nullable|string',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email',
            'admin_password' => 'required|min:6',
        ]);

        $tenant = Tenant::create([
            'id'      => $validated['slug'],
            'name'    => $validated['name'],
            'slug'    => $validated['slug'],
            'phone'   => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'plan'    => $validated['plan'] ?? 'basic',
            'status'  => 'trial',
        ]);

        $tenant->createDomain(['domain' => $validated['slug'] . '.med.eversoft.uz']);

        $adminData = [
            'name'     => $validated['admin_name'],
            'email'    => $validated['admin_email'],
            'password' => $validated['admin_password'],
            'role'     => 'admin',
            'is_active' => true,
        ];

        $tenant->run(function () use ($adminData) {
            \Artisan::call('migrate', [
                '--path'  => 'database/migrations/tenant',
                '--force' => true,
            ]);

            User::create($adminData);
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
