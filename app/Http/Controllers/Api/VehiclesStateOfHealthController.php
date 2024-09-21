<?php

namespace App\Http\Controllers\Api;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\StateOfHealthResource;
use App\Http\Resources\StateOfHealthCollection;

class VehiclesStateOfHealthController extends Controller
{
    public function index(
        Request $request,
        Vehicle $vehicle
    ): StateOfHealthCollection {
        $search = $request->get('search', '');

        $stateOfHealths = $this->getSearchQuery($search, $vehicle)
            ->latest()
            ->paginate();

        return new StateOfHealthCollection($stateOfHealths);
    }

    public function store(
        Request $request,
        Vehicle $vehicle
    ): StateOfHealthResource {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:1024'],
            'km' => ['required'],
            'percentage' => ['required'],
            'remaining_battery' => ['nullable'],
            'user_id' => ['required'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('public');
        }

        $stateOfHealth = $vehicle->stateOfHealths()->create($validated);

        return new StateOfHealthResource($stateOfHealth);
    }

    public function getSearchQuery(string $search, Vehicle $vehicle)
    {
        return $vehicle
            ->stateOfHealths()
            ->where('image', 'like', "%{$search}%");
    }
}
