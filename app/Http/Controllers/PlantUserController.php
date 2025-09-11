<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\PlantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getPlantsUser(Request $request): JsonResponse
    {
        /**
         * @var \App\Models\User $user
         */
        $user = $request->user();

        $plants = $user->plants;

        return response()->json($plants, 200);
    }


    public function addPlantUser(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'plant_name' => 'required|string',
            'city' => 'required|string',
        ]);

        /**
         * @var \App\Models\User $user
         */
        $user = $request->user();

        $plant = Plant::where('common_name', 'LIKE', '%' . $validated['plant_name'] . '%')->firstOrFail();
        if (!$plant) {
            return response()->json(['error' => 'Plant not found'], 404);
        }

        $city = $validated['city'];

        $user->plants()->attach($plant->id, ['city' => $city]);

        return response()->json([
            'message' => 'Plant added to user successfully',
        ], 200);
    }

    public function deletePlantUser(Request $request, int $id): JsonResponse
    {
        /**
         * @var \App\Models\User $user
         */
        $user = $request->user();

        $relation = $user->plants()->wherePivot('id', $id)->first();

        if (!$relation) {
            return response()->json(['error' => 'Plant not found in user'], 404);
        }

        $user->plants()->wherePivot('id', $id)->detach();

        return response()->json(['message' => 'Plant deleted from user successfully'], 200);
    }
}
