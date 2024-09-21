<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ChargerLocation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ChargerLocationResource;
use App\Http\Resources\ChargerLocationCollection;
use App\Http\Requests\ChargerLocationStoreRequest;
use App\Http\Requests\ChargerLocationUpdateRequest;

class ChargerLocationController extends Controller
{
    public function index(Request $request): ChargerLocationCollection
    {
        $search = $request->get('search', '');

        $chargerLocations = $this->getSearchQuery($search)
            ->latest()
            ->paginate();

        return new ChargerLocationCollection($chargerLocations);
    }

    public function store(
        ChargerLocationStoreRequest $request
    ): ChargerLocationResource {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('public');
        }

        $chargerLocation = ChargerLocation::create($validated);

        return new ChargerLocationResource($chargerLocation);
    }

    public function show(
        Request $request,
        ChargerLocation $chargerLocation
    ): ChargerLocationResource {
        return new ChargerLocationResource($chargerLocation);
    }

    public function update(
        ChargerLocationUpdateRequest $request,
        ChargerLocation $chargerLocation
    ): ChargerLocationResource {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($chargerLocation->image) {
                Storage::delete($chargerLocation->image);
            }

            $validated['image'] = $request->file('image')->store('public');
        }

        $chargerLocation->update($validated);

        return new ChargerLocationResource($chargerLocation);
    }

    public function destroy(
        Request $request,
        ChargerLocation $chargerLocation
    ): Response {
        if ($chargerLocation->image) {
            Storage::delete($chargerLocation->image);
        }

        $chargerLocation->delete();

        return response()->noContent();
    }

    public function getSearchQuery(string $search)
    {
        return ChargerLocation::query()->where('name', 'like', "%{$search}%");
    }
}
