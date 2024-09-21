<?php

namespace App\Http\Controllers\Api;

use App\Models\Charger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChargerResource;
use App\Http\Resources\ChargerCollection;
use App\Http\Requests\ChargerStoreRequest;
use App\Http\Requests\ChargerUpdateRequest;

class ChargerController extends Controller
{
    public function index(Request $request): ChargerCollection
    {
        $search = $request->get('search', '');

        $chargers = $this->getSearchQuery($search)
            ->latest()
            ->paginate();

        return new ChargerCollection($chargers);
    }

    public function store(ChargerStoreRequest $request): ChargerResource
    {
        $validated = $request->validated();

        $charger = Charger::create($validated);

        return new ChargerResource($charger);
    }

    public function show(Request $request, Charger $charger): ChargerResource
    {
        return new ChargerResource($charger);
    }

    public function update(
        ChargerUpdateRequest $request,
        Charger $charger
    ): ChargerResource {
        $validated = $request->validated();

        $charger->update($validated);

        return new ChargerResource($charger);
    }

    public function destroy(Request $request, Charger $charger): Response
    {
        $charger->delete();

        return response()->noContent();
    }

    public function getSearchQuery(string $search)
    {
        return Charger::query()->where('created_at', 'like', "%{$search}%");
    }
}
