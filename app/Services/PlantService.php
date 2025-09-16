<?php

namespace App\Services;

use App\Models\ApiSyncTracking;
use App\Models\Plant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PlantService
{
    private string $apiKey;
    private string $apiUrl;
    private int $dailyLimit;
    private ApiSyncTracking $tracking;

    public function __construct()
    {
        $this->apiKey = config('services.perenual.key');
        $this->apiUrl = 'https://perenual.com/api/v2/species/details';
        $this->dailyLimit = config('services.perenual.daily_limit', 100);
        $this->tracking = ApiSyncTracking::getTodaysTracking();
    }

    public function fetchAndStorePlants(int $maxRequests = null): array
    {
        // Si aucune limite n'est spécifiée, utiliser la limite quotidienne
        $requestLimit = $maxRequests ?? $this->dailyLimit;

        // Calculer les requêtes restantes pour aujourd'hui
        $remainingDailyRequests = $this->dailyLimit - $this->tracking->daily_request_count;

        if ($remainingDailyRequests <= 0) {
            Log::info("Limite quotidienne atteinte pour aujourd'hui ({$this->tracking->daily_request_count}/{$this->dailyLimit})");
            return $this->getStats();
        }

        // Utiliser le minimum entre la limite demandée et les requêtes restantes
        $actualLimit = min($requestLimit, $remainingDailyRequests);

        Log::info("Début de la synchronisation - ID de départ: {$this->tracking->last_processed_id}, Requêtes à effectuer: {$actualLimit}");


        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'starting_id' => $this->tracking->last_processed_id,
        ];

        for ($i = 0; $i < $actualLimit; $i++) {
            $currentId = $this->tracking->last_processed_id + 1;

            try {
                $result = $this->fetchAndStorePlant($currentId);

                if ($result) {
                    $stats['processed']++;
                    $stats[$result['action']]++;
                    $this->tracking->successful_requests++;
                } else {
                    // API a retourné 404 ou pas de données
                    $stats['errors']++;
                    $this->tracking->failed_requests++;

                    // Si on a plusieurs 404 consécutifs, on peut arrêter
                    if ($this->shouldStopOnConsecutiveErrors($currentId)) {
                        Log::warning("Arrêt de la synchronisation - trop d'erreurs consécutives à partir de l'ID {$currentId}");
                        break;
                    }
                }

                $this->tracking->last_processed_id = $currentId;
                $this->tracking->daily_request_count++;
                $this->tracking->total_requests_made++;
                $this->tracking->save();

                // Respecter les limites de rate limiting
                sleep(1);
            } catch (\Exception $e) {
                Log::error("Erreur lors de la récupération de la plante ID {$currentId}: " . $e->getMessage());
                $stats['errors']++;
                $this->tracking->failed_requests++;
                $this->tracking->last_processed_id = $currentId;
                $this->tracking->daily_request_count++;
                $this->tracking->total_requests_made++;
                $this->tracking->save();
            }
        }

        $stats['ending_id'] = $this->tracking->last_processed_id;
        $stats['total_requests_today'] = $this->tracking->daily_request_count;
        $stats['actual_limit_used'] = $actualLimit;


        Log::info("Synchronisation terminée", $stats);

        return $stats;
    }

    private function fetchAndStorePlant(int $id): ?array
    {
        $response = Http::timeout(30)->get("{$this->apiUrl}/{$id}", [
            'key' => $this->apiKey
        ]);

        if (!$response->successful()) {
            if ($response->status() === 404) {
                Log::debug("Plante ID {$id} non trouvée (404)");
                return null;
            }

            Log::error("Erreur API pour l'ID {$id}: Status {$response->status()}");
            return null;
        }

        $data = $response->json();

        if (!$data || !isset($data['id'])) {
            Log::warning("Données invalides reçues pour l'ID {$id}");
            return null;
        }

        return $this->storePlant($data);
    }

    private function storePlant(array $data): array
    {
        $plantData = [
            'api_id' => $data['id'],
            'common_name' => $data['common_name'] ?? 'Unknown',
            'watering_general_benchmark' => $this->formatWateringBenchmark($data),
            'watering' => $data['watering'] ?? null,
            'watering_period' => $data['watering_period'] ?? null,
            'flowers' => $data['flowers'] ?? null,
            'fruits' => $data['fruits'] ?? null,
            'leaf' => $data['leaf'] ?? null,
            'growth_rate' => $data['growth_rate'] ?? null,
            'maintenance' => $data['maintenance'] ?? null,
            'last_synced_at' => Carbon::now(),
        ];

        $plant = Plant::updateOrCreate(
            ['api_id' => $data['id']],
            $plantData
        );

        return [
            'action' => $plant->wasRecentlyCreated ? 'created' : 'updated',
            'plant' => $plant
        ];
    }

    private function formatWateringBenchmark(array $data): array
    {
        if (isset($data['watering_general_benchmark']['value']) && isset($data['watering_general_benchmark']['unit'])) {
            return $data['watering_general_benchmark'];
        }

        // Fallback si la structure est différente
        return [
            'value' => $data['watering_period'] ?? '7',
            'unit' => 'days'
        ];
    }

    private function shouldStopOnConsecutiveErrors(int $currentId): bool
    {
        // Arrêter si on a 10 erreurs consécutives (IDs non trouvés)
        $consecutiveErrors = 0;
        $checkRange = 10;

        for ($i = max(1, $currentId - $checkRange); $i < $currentId; $i++) {
            if (!Plant::where('api_id', $i)->exists()) {
                $consecutiveErrors++;
            } else {
                $consecutiveErrors = 0; // Reset si on trouve une plante
            }
        }

        return $consecutiveErrors >= $checkRange;
    }

    public function getStats(): array
    {
        return [
            'last_processed_id' => $this->tracking->last_processed_id,
            'daily_request_count' => $this->tracking->daily_request_count,
            'daily_limit' => $this->dailyLimit,
            'remaining_requests' => max(0, $this->dailyLimit - $this->tracking->daily_request_count),
            'successful_requests' => $this->tracking->successful_requests,
            'failed_requests' => $this->tracking->failed_requests,
            'last_sync_date' => $this->tracking->last_sync_date->format('Y-m-d'),
            'total_plants_in_db' => Plant::count(),
        ];
    }

    public function resetProgress(): void
    {
        $this->tracking->update([
            'last_processed_id' => 0,
            'daily_request_count' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
        ]);

        Log::info("Progression réinitialisée");
    }
}
