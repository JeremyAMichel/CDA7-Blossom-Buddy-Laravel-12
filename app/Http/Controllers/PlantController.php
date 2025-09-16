<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/plants",
     *     summary="Liste toutes les plantes",
     *     description="Récupérer la liste complète de toutes les plantes disponibles",
     *     tags={"Plants"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plantes récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="common_name", type="string", example="Rose"),
     *                 @OA\Property(property="watering_general_benchmark", type="object",
     *                     @OA\Property(property="value", type="string", example="7"),
     *                     @OA\Property(property="unit", type="string", example="days")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json(Plant::all());
    }

    /**
     * @OA\Post(
     *     path="/api/plants",
     *     summary="Créer une nouvelle plante",
     *     description="Ajouter une nouvelle plante à la base de données",
     *     tags={"Plants"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données de la plante à créer",
     *         @OA\JsonContent(
     *             required={"common_name","watering_general_benchmark"},
     *             @OA\Property(property="common_name", type="string", example="Rose", description="Nom commun de la plante"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 required={"value","unit"},
     *                 @OA\Property(property="value", type="string", example="7", description="Fréquence d'arrosage"),
     *                 @OA\Property(property="unit", type="string", example="days", description="Unité de temps")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plante créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="common_name", type="string", example="Rose"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 @OA\Property(property="value", type="string", example="7"),
     *                 @OA\Property(property="unit", type="string", example="days")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $plant = Plant::create($request->all());

        return response()->json($plant, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/plants/{common_name}",
     *     summary="Afficher une plante spécifique",
     *     description="Récupérer les détails d'une plante par son nom commun (recherche partielle)",
     *     tags={"Plants"},
     *     @OA\Parameter(
     *         name="common_name",
     *         in="path",
     *         required=true,
     *         description="Nom commun de la plante (recherche partielle avec LIKE)",
     *         @OA\Schema(type="string"),
     *         example="Rose"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la plante",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="common_name", type="string", example="Rose"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 @OA\Property(property="value", type="string", example="7"),
     *                 @OA\Property(property="unit", type="string", example="days")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Plant]")
     *         )
     *     )
     * )
     */
    public function show(string $common_name): JsonResponse
    {
        $plant = Plant::where('common_name', 'LIKE', '%' . $common_name . '%')->firstOrFail();
        return response()->json($plant);
    }

    /**
     * @OA\Put(
     *     path="/api/plants/{common_name}",
     *     summary="Mettre à jour une plante",
     *     description="Modifier les informations d'une plante existante (mise à jour partielle possible)",
     *     tags={"Plants"},
     *     @OA\Parameter(
     *         name="common_name",
     *         in="path",
     *         required=true,
     *         description="Nom commun de la plante à modifier",
     *         @OA\Schema(type="string"),
     *         example="Rose"
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Données à mettre à jour (tous les champs sont optionnels)",
     *         @OA\JsonContent(
     *             @OA\Property(property="common_name", type="string", example="Rose Rouge", description="Nouveau nom commun"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 @OA\Property(property="value", type="string", example="10", description="Nouvelle fréquence d'arrosage"),
     *                 @OA\Property(property="unit", type="string", example="days", description="Nouvelle unité de temps")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="common_name", type="string", example="Rose Rouge"),
     *             @OA\Property(property="watering_general_benchmark", type="object",
     *                 @OA\Property(property="value", type="string", example="10"),
     *                 @OA\Property(property="unit", type="string", example="days")
     *             ),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plant not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/plants/{common_name}",
     *     summary="Supprimer une plante",
     *     description="Supprimer définitivement une plante de la base de données",
     *     tags={"Plants"},
     *     @OA\Parameter(
     *         name="common_name",
     *         in="path",
     *         required=true,
     *         description="Nom commun de la plante à supprimer",
     *         @OA\Schema(type="string"),
     *         example="Rose"
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Plante supprimée avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plant not found")
     *         )
     *     )
     * )
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
