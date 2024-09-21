<?php

namespace App\Http\Controllers\Api;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\VehicleCollection;
use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;

class VehicleController extends Controller
{
    public function index(Request $request): VehicleCollection
    {
        $search = $request->get('search', '');

        $vehicles = $this->getSearchQuery($search)
            ->latest()
            ->paginate();

        return new VehicleCollection($vehicles);
    }

    public function store(VehicleStoreRequest $request): VehicleResource
    {
        $validated = $request->validated();

        $vehicle = Vehicle::create($validated);

        return new VehicleResource($vehicle);
    }

    public function show(Request $request, Vehicle $vehicle): VehicleResource
    {
        return new VehicleResource($vehicle);
    }

    public function update(
        VehicleUpdateRequest $request,
        Vehicle $vehicle
    ): VehicleResource {
        $validated = $request->validated();

        $vehicle->update($validated);

        return new VehicleResource($vehicle);
    }

    public function destroy(Request $request, Vehicle $vehicle): Response
    {
        $vehicle->delete();

        return response()->noContent();
    }

    public function getSearchQuery(string $search)
    {
        return Vehicle::query();
    }
}
