<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUserEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->query('search'), fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->query('status'), fn($q, $v) => $q->where('status', $v))
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
            'name'      => $validated['admin_name'],
            'email'     => $validated['admin_email'],
            'password'  => $validated['admin_password'],
            'role'      => 'admin',
            'is_active' => true,
        ];

        $tenant->run(function () use ($adminData) {
            Artisan::call('migrate', [
                '--path'  => 'database/migrations/tenant',
                '--force' => true,
            ]);
            User::create($adminData);
        });

        TenantUserEmail::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $validated['admin_email']]
        );

        return response()->json($tenant, 201);
    }

    public function show(string $id)
    {
        $tenant = Tenant::with(['subscriptions', 'domains'])->findOrFail($id);

        $adminEmail = null;
        try {
            $tenant->run(function () use (&$adminEmail) {
                $adminEmail = User::where('role', 'admin')->value('email');
            });
        } catch (\Exception $e) {}

        return response()->json(array_merge($tenant->toArray(), ['admin_email' => $adminEmail]));
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'slug'        => ['sometimes', 'string', 'regex:/^[a-z0-9\-]+$/', Rule::unique('tenants', 'slug')->ignore($id)],
            'phone'       => 'nullable|string',
            'address'     => 'nullable|string',
            'status'      => 'sometimes|in:active,trial,suspended',
            'plan'        => 'nullable|string',
            'admin_email' => 'nullable|email',
        ]);

        // Slug changed → update subdomain record
        if (isset($validated['slug']) && $validated['slug'] !== $tenant->slug) {
            $tenant->domains()->delete();
            $tenant->createDomain(['domain' => $validated['slug'] . '.med.eversoft.uz']);
        }

        // Admin email changed → update in tenant DB + central mapping
        if (!empty($validated['admin_email'])) {
            $newEmail = $validated['admin_email'];
            $oldEmail = null;

            $tenant->run(function () use ($newEmail, &$oldEmail) {
                $admin = User::where('role', 'admin')->first();
                if ($admin) {
                    $oldEmail = $admin->email;
                    $admin->update(['email' => $newEmail]);
                }
            });

            if ($oldEmail && $oldEmail !== $newEmail) {
                TenantUserEmail::where('tenant_id', $id)
                    ->where('email', $oldEmail)
                    ->update(['email' => $newEmail]);
            }
        }

        // Remove virtual field before saving to tenants table
        $tenantFields = array_diff_key($validated, ['admin_email' => null]);
        $tenant->update(array_filter($tenantFields, fn($v) => !is_null($v)));

        return response()->json($tenant->fresh());
    }

    public function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        // Clean up central email mappings
        TenantUserEmail::where('tenant_id', $id)->delete();

        // Delete subdomains
        $tenant->domains()->delete();

        // Delete tenant — stancl/tenancy drops the tenant DB automatically
        $tenant->delete();

        return response()->json(['message' => "Klinika o'chirildi"]);
    }

    public function resetAdminPassword(Request $request, string $id)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $tenant = Tenant::findOrFail($id);

        $tenant->run(function () use ($request) {
            $user = User::where('email', $request->email)
                ->where('role', 'admin')
                ->firstOrFail();

            $user->update(['password' => Hash::make($request->password)]);
        });

        return response()->json(['message' => 'Parol yangilandi']);
    }
}
