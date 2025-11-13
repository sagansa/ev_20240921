<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\LocationCategory;

class LocationCategoryController extends Controller
{
    public function index()
    {
        $categories = LocationCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (LocationCategory $category) => [
                'id' => (string) $category->id,
                'name' => $category->name,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Location categories retrieved successfully',
            'data' => $categories,
        ]);
    }
}
