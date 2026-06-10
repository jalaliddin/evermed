<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with('category')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->paginate($request->per_page ?? 50);

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:service_categories,id',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        return response()->json(Service::create($validated), 201);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:service_categories,id',
            'price' => 'sometimes|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);
        return response()->json($service->load('category'));
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function categories()
    {
        return response()->json(ServiceCategory::withCount('services')->get());
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        return response()->json(ServiceCategory::create($validated), 201);
    }

    public function updateCategory(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $serviceCategory->update($validated);
        return response()->json($serviceCategory);
    }

    public function destroyCategory(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
