<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if it's a super admin login
        $admin = Admin::where('email', $request->email)->first();
        if ($admin && \Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('admin-token', ['role:super_admin'])->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $admin,
                'type' => 'admin',
            ]);
        }

        // Check tenant user
        $user = User::where('email', $request->email)->first();
        if ($user && \Hash::check($request->password, $user->password)) {
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Foydalanuvchi bloklangan.'],
                ]);
            }
            $user->update(['last_login_at' => now()]);
            $token = $user->createToken('user-token', ['role:' . $user->role])->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $user->load('doctor'),
                'type' => 'tenant',
            ]);
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
