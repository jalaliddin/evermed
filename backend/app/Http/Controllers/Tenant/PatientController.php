<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = Patient::query()
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($q) use ($s) {
                    $q->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%")
                      ->orWhere('birth_date', 'like', "%{$s}%");
                });
            })
            ->when($request->gender, fn($q) => $q->where('gender', $request->gender))
            ->when($request->blood_type, fn($q) => $q->where('blood_type', $request->blood_type))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'allergies' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $patient = Patient::create($validated);
        return response()->json($patient, 201);
    }

    public function show(Patient $patient)
    {
        return response()->json($patient);
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'allergies' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($patient->photo) Storage::disk('public')->delete($patient->photo);
            $validated['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $patient->update($validated);
        return response()->json($patient);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function visits(Patient $patient)
    {
        $visits = $patient->visits()
            ->with(['doctor.user', 'services.service', 'inventory.item'])
            ->latest('visited_at')
            ->paginate(20);
        return response()->json($visits);
    }

    public function stats(Patient $patient)
    {
        return response()->json([
            'total_visits' => $patient->visits()->count(),
            'total_spent' => $patient->visits()->sum('paid_amount'),
            'last_visit' => $patient->visits()->latest('visited_at')->first()?->visited_at,
        ]);
    }
}
