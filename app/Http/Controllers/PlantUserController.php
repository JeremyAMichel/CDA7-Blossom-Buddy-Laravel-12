<?php

namespace App\Http\Controllers;

use App\Contracts\WeatherServiceInterface;
use App\Models\Plant;
use App\Models\PlantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlantUserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/plants",
     *     summary="Récupérer les plantes de l'utilisateur",
     *     description="Obtenir la liste de toutes les plantes associées à l'utilisateur connecté avec les informations de ville",
     *     tags={"User Plants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plantes de l'utilisateur récupérée avec succès",
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
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="pivot", type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="ID de la relation plant_user"),
     *                     @OA\Property(property="city", type="string", example="Paris", description="Ville où se trouve la plante"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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

    /**
     * @OA\Post(
     *     path="/api/user/plant",
     *     summary="Ajouter une plante à l'utilisateur",
     *     description="Associer une plante existante à l'utilisateur connecté avec une ville spécifique",
     *     tags={"User Plants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données pour ajouter une plante à l'utilisateur",
     *         @OA\JsonContent(
     *             required={"plant_name","city"},
     *             @OA\Property(property="plant_name", type="string", example="Rose", description="Nom de la plante (recherche partielle)"),
     *             @OA\Property(property="city", type="string", example="Paris", description="Ville où se trouve la plante")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante ajoutée avec succès à l'utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plant added to user successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plante non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Plant]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="plant_name", type="array", @OA\Items(type="string", example="The plant name field is required.")),
     *                 @OA\Property(property="city", type="array", @OA\Items(type="string", example="The city field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function addPlantUser(Request $request, WeatherServiceInterface $weatherService): JsonResponse
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
        if(!$plant) {
            return response()->json(['message' => 'Plant not found'], 404);
        }

        $city = $validated['city'];

        // Call the weather service

        $user->plants()->attach($plant->id, ['city' => $city]);

        // Récupérer les données météo
        try {
            // Déterminer le nombre de jours basé sur le watering benchmark de la plante
            $forecastDays = $weatherService->determineForecastDays($plant->watering_general_benchmark);
            
            // Récupérer les prévisions météo
            $weatherData = $weatherService->getForecast($city, $forecastDays);

            return response()->json([
                'message' => 'Plant added to user successfully',
                'weather_info' => [
                    'city' => $weatherData['city'],
                    'days' => $weatherData['days'],
                    'daily_humidity' => $weatherData['daily_humidity'],
                    'retrieved_at' => $weatherData['retrieved_at']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de la météo pour {$city}: " . $e->getMessage());
            
            // La plante est déjà ajoutée, mais on informe que les données météo ne sont pas disponibles
            return response()->json([
                'message' => 'Plant added to user successfully, but weather data unavailable',
                'error' => 'Weather data could not be retrieved: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/user/plant/{id}",
     *     summary="Supprimer une plante de l'utilisateur",
     *     description="Retirer l'association entre l'utilisateur connecté et une plante spécifique",
     *     tags={"User Plants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la relation plant_user (pivot.id)",
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plante supprimée avec succès de l'utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Plant deleted from user successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Relation plante-utilisateur non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Plant not found in user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
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
