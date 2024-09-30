<?php

namespace App\Http\Controllers\Api;

use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeResource;
use App\Http\Resources\ChargeCollection;
use App\Http\Requests\ChargeStoreRequest;
use App\Http\Requests\ChargeUpdateRequest;

class ChargeController extends Controller
{
    public function index(Request $request): ChargeCollection
    {
        $search = $request->get('search', '');

        $charges = $this->getSearchQuery($search)
            ->latest()
            ->paginate();

        return new ChargeCollection($charges);
    }

    public function store(ChargeStoreRequest $request): ChargeResource
    {
        $validated = $request->validated();

        $charge = Charge::create($validated);

        return new ChargeResource($charge);
    }

    public function show(Request $request, Charge $charge): ChargeResource
    {
        return new ChargeResource($charge);
    }

    public function update(
        ChargeUpdateRequest $request,
        Charge $charge
    ): ChargeResource {
        $validated = $request->validated();

        $charge->update($validated);

        return new ChargeResource($charge);
    }

    public function destroy(Request $request, Charge $charge): Response
    {
        $charge->delete();

        return response()->noContent();
    }

    public function getSearchQuery(string $search)
    {
        return Charge::query()->where('date', 'like', "%{$search}%");
    }
}
