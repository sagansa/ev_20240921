<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\StateOfHealth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\StateOfHealthResource;
use App\Http\Resources\StateOfHealthCollection;
use App\Http\Requests\StateOfHealthStoreRequest;
use App\Http\Requests\StateOfHealthUpdateRequest;

class StateOfHealthController extends Controller
{
    public function index(Request $request): StateOfHealthCollection
    {
        $search = $request->get('search', '');

        $stateOfHealths = $this->getSearchQuery($search)
            ->latest()
            ->paginate();

        return new StateOfHealthCollection($stateOfHealths);
    }

    public function store(
        StateOfHealthStoreRequest $request
    ): StateOfHealthResource {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('public');
        }

        $stateOfHealth = StateOfHealth::create($validated);

        return new StateOfHealthResource($stateOfHealth);
    }

    public function show(
        Request $request,
        StateOfHealth $stateOfHealth
    ): StateOfHealthResource {
        return new StateOfHealthResource($stateOfHealth);
    }

    public function update(
        StateOfHealthUpdateRequest $request,
        StateOfHealth $stateOfHealth
    ): StateOfHealthResource {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($stateOfHealth->image) {
                Storage::delete($stateOfHealth->image);
            }

            $validated['image'] = $request->file('image')->store('public');
        }

        $stateOfHealth->update($validated);

        return new StateOfHealthResource($stateOfHealth);
    }

    public function destroy(
        Request $request,
        StateOfHealth $stateOfHealth
    ): Response {
        if ($stateOfHealth->image) {
            Storage::delete($stateOfHealth->image);
        }

        $stateOfHealth->delete();

        return response()->noContent();
    }

    public function getSearchQuery(string $search)
    {
        return StateOfHealth::query()->where('image', 'like', "%{$search}%");
    }
}
