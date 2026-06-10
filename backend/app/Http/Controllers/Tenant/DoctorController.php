<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = Doctor::with('user')
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->paginate($request->per_page ?? 15);

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
            'specialization' => 'nullable|string',
            'room_number' => 'nullable|string',
            'consultation_price' => 'nullable|numeric|min:0',
            'schedule' => 'nullable|array',
            'bio' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $request, &$doctor) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'doctor',
                'phone' => $validated['phone'] ?? null,
            ];

            if ($request->hasFile('avatar')) {
                $userData['avatar'] = $request->file('avatar')->store('users/avatars', 'public');
            }

            $user = User::create($userData);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization' => $validated['specialization'] ?? null,
                'room_number' => $validated['room_number'] ?? null,
                'consultation_price' => $validated['consultation_price'] ?? 0,
                'schedule' => $validated['schedule'] ?? null,
                'bio' => $validated['bio'] ?? null,
            ]);
        });

        return response()->json($doctor->load('user'), 201);
    }

    public function show(Doctor $doctor)
    {
        return response()->json($doctor->load('user'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'specialization' => 'nullable|string',
            'room_number' => 'nullable|string',
            'consultation_price' => 'nullable|numeric|min:0',
            'schedule' => 'nullable|array',
            'bio' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($validated, $request, $doctor) {
            $doctor->user->update(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]));

            $doctor->update(array_filter([
                'specialization' => $validated['specialization'] ?? null,
                'room_number' => $validated['room_number'] ?? null,
                'consultation_price' => $validated['consultation_price'] ?? null,
                'schedule' => $validated['schedule'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
            ], fn($v) => !is_null($v)));
        });

        return response()->json($doctor->load('user'));
    }

    public function report(Request $request, Doctor $doctor)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $totalPatients = $doctor->visits()
            ->whereBetween('visited_at', [$from, $to])
            ->distinct('patient_id')->count('patient_id');

        $totalRevenue = $doctor->visits()
            ->whereBetween('visited_at', [$from, $to])
            ->where('is_paid', true)
            ->sum('paid_amount');

        $totalAppointments = $doctor->appointments()
            ->whereBetween('scheduled_at', [$from, $to])
            ->count();

        return response()->json([
            'doctor' => $doctor->load('user'),
            'period' => compact('from', 'to'),
            'stats' => compact('totalPatients', 'totalRevenue', 'totalAppointments'),
        ]);
    }

    public function appointments(Request $request, Doctor $doctor)
    {
        $appointments = $doctor->appointments()
            ->with('patient', 'service')
            ->when($request->date, fn($q) => $q->whereDate('scheduled_at', $request->date))
            ->orderBy('scheduled_at')
            ->paginate(15);

        return response()->json($appointments);
    }
}
