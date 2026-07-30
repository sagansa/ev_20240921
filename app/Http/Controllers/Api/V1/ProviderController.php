<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = Provider::where('status', 1)
            ->whereHas('spkluLocations')
            ->withCount('spkluLocations');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $providers = $query->orderByDesc('spklu_locations_count')->get();

        return ProviderResource::collection($providers)
            ->additional(['status' => 'success']);
    }
}
