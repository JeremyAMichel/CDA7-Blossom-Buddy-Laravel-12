<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(Plant::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $plant = Plant::create($request->all());

        return response()->json($plant, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $common_name): JsonResponse
    {
        $plant = Plant::where('common_name', 'LIKE', '%' . $common_name . '%')->firstOrFail();
        return response()->json($plant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $common_name): JsonResponse
    {
        $validatedData = $request->validate([
            'common_name' => 'sometimes|string|max:255',
            'watering_general_benchmark' => 'sometimes|array',
            'watering_general_benchmark.value' => 'sometimes|string',
            'watering_general_benchmark.unit' => 'sometimes|string',
        ]);

        try {
            $plant = Plant::where('common_name', 'LIKE', '%' . $common_name . '%')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Plant not found'], 404);
        }

        if (isset($validatedData['common_name'])) {
            $plant->common_name = $validatedData['common_name'];
        }
    
        if (isset($validatedData['watering_general_benchmark'])) {
            $wateringBenchmark = $plant->watering_general_benchmark;
    
            if (isset($validatedData['watering_general_benchmark']['value'])) {
                $wateringBenchmark['value'] = $validatedData['watering_general_benchmark']['value'];
            }
    
            if (isset($validatedData['watering_general_benchmark']['unit'])) {
                $wateringBenchmark['unit'] = $validatedData['watering_general_benchmark']['unit'];
            }
    
            $plant->watering_general_benchmark = $wateringBenchmark;
        }
    
        $plant->save();

        return response()->json($plant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $plant = Plant::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Plant not found'], 404);
        }

        $plant->delete();

        return response()->json(null, 204);
    }
}
