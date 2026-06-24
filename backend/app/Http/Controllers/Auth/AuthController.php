<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Tenant;
use App\Models\TenantUserEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $email    = $request->input('email');
        $password = $request->input('password');

        // Super admin (central DB)
        $admin = Admin::where('email', $email)->first();
        if ($admin && Hash::check($password, $admin->password)) {
            $token = $admin->createToken('admin-token', ['role:super_admin'])->plainTextToken;
            return response()->json([
                'token' => $token,
                'user'  => $admin,
                'type'  => 'admin',
            ]);
        }

        // Find which tenant this email belongs to
        $map = TenantUserEmail::where('email', $email)->first();
        if (!$map) {
            throw ValidationException::withMessages([
                'email' => ['Email yoki parol noto\'g\'ri.'],
            ]);
        }

        $tenant = Tenant::find($map->tenant_id);
        if (!$tenant) {
            throw ValidationException::withMessages([
                'email' => ['Klinika topilmadi.'],
            ]);
        }

        $result = null;
        tenancy()->initialize($tenant);

        try {
            $user = User::where('email', $email)->first();

            if ($user && Hash::check($password, $user->password)) {
                if (!$user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => ['Foydalanuvchi bloklangan.'],
                    ]);
                }
                $user->update(['last_login_at' => now()]);
                $token = $user->createToken('user-token', ['role:' . $user->role])->plainTextToken;
                $result = [
                    'token'     => $token,
                    'user'      => array_merge($user->load('doctor')->toArray(), ['tenant_id' => $map->tenant_id]),
                    'type'      => 'tenant',
                    'tenant_id' => $map->tenant_id,
                ];
            }
        } finally {
            tenancy()->end();
        }

        if ($result) {
            return response()->json($result);
        }

        throw ValidationException::withMessages([
            'email' => ['Email yoki parol noto\'g\'ri.'],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        if (tenancy()->initialized) tenancy()->end();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if ($user instanceof Admin) {
            return response()->json(['user' => $user, 'type' => 'admin']);
        }
        $tenantId = $request->header('X-Tenant') ?? $request->query('tenant');
        return response()->json([
            'user'      => $user->load('doctor'),
            'type'      => 'tenant',
            'tenant_id' => $tenantId,
        ]);
    }
}
