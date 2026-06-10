<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Events\AppointmentStatusChanged;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::with(['patient', 'doctor.user', 'service'])
            ->when($request->doctor_id, fn($q) => $q->where('doctor_id', $request->doctor_id))
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('scheduled_at', $request->date))
            ->when($request->from, fn($q) => $q->where('scheduled_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->where('scheduled_at', '<=', $request->to))
            ->orderBy('scheduled_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'service_id' => 'nullable|exists:services,id',
            'scheduled_at' => 'required|date',
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $appointment = Appointment::create($validated);

        return response()->json($appointment->load(['patient', 'doctor.user', 'service']), 201);
    }

    public function show(Appointment $appointment)
    {
        return response()->json($appointment->load(['patient', 'doctor.user', 'service', 'visit']));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'service_id' => 'nullable|exists:services,id',
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);
        return response()->json($appointment->load(['patient', 'doctor.user', 'service']));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        $oldStatus = $appointment->status;
        $appointment->update(['status' => $request->status]);

        event(new AppointmentStatusChanged($appointment, $oldStatus));

        return response()->json($appointment->load(['patient', 'doctor.user']));
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function availableSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
        ]);

        $doctor = Doctor::findOrFail($request->doctor_id);
        $dayName = strtolower(date('l', strtotime($request->date)));
        $schedule = $doctor->schedule[$dayName] ?? null;

        if (!$schedule) {
            return response()->json([]);
        }

        $bookedSlots = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('scheduled_at', $request->date)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->pluck('scheduled_at')
            ->map(fn($dt) => $dt->format('H:i'))
            ->toArray();

        $slots = [];
        $start = strtotime($schedule['start']);
        $end = strtotime($schedule['end']);
        $interval = 30 * 60; // 30 minutes

        for ($time = $start; $time < $end; $time += $interval) {
            $slot = date('H:i', $time);
            if (!in_array($slot, $bookedSlots)) {
                $slots[] = $slot;
            }
        }

        return response()->json($slots);
    }
}
