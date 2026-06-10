<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Tenant;
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
            'tenant'   => 'nullable|string',
        ]);

        // Super admin login (central DB, no tenant needed)
        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('admin-token', ['role:super_admin'])->plainTextToken;
            return response()->json([
                'token' => $token,
                'user'  => $admin,
                'type'  => 'admin',
            ]);
        }

        // Tenant user login — requires tenant slug
        $tenantSlug = $request->tenant
            ?? $request->header('X-Tenant');

        if (!$tenantSlug) {
            throw ValidationException::withMessages([
                'tenant' => ['Klinika slugini kiriting.'],
            ]);
        }

        $tenant = Tenant::find($tenantSlug);
        if (!$tenant) {
            throw ValidationException::withMessages([
                'tenant' => ['Klinika topilmadi.'],
            ]);
        }

        $result = null;
        tenancy()->initialize($tenant);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if (!$user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => ['Foydalanuvchi bloklangan.'],
                    ]);
                }
                $user->update(['last_login_at' => now()]);
                $token = $user->createToken('user-token', ['role:' . $user->role])->plainTextToken;
                $result = [
                    'token'     => $token,
                    'user'      => array_merge($user->load('doctor')->toArray(), ['tenant_id' => $tenantSlug]),
                    'type'      => 'tenant',
                    'tenant_id' => $tenantSlug,
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
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if ($user instanceof Admin) {
            return response()->json(['user' => $user, 'type' => 'admin']);
        }
        return response()->json(['user' => $user->load('doctor'), 'type' => 'tenant']);
    }
}
