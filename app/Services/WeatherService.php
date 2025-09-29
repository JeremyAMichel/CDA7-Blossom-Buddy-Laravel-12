<?php
// filepath: app/Services/WeatherService.php

namespace App\Services;

use App\Contracts\WeatherServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService implements WeatherServiceInterface
{
    private string $apiKey;
    private string $baseUrl;
    private int $cacheDuration;
    private int $maxForecastDays;

    public function __construct()
    {
        $this->apiKey = config('services.weatherapi.key');
        $this->baseUrl = config('services.weatherapi.base_url');
        $this->cacheDuration = config('services.weatherapi.cache_duration', 120);
        $this->maxForecastDays = config('services.weatherapi.max_forecast_days', 5);
    }

    public function getForecast(string $city, int $days = 5): array
    {
        // Limiter le nombre de jours au maximum autorisé
        $days = min($days, $this->maxForecastDays);
        
        // Clé de cache unique pour cette ville et ces jours
        $cacheKey = "weather_forecast_{$city}_{$days}_days";
        
        // Essayer de récupérer depuis le cache
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            Log::info("Données météo récupérées depuis le cache pour {$city}");
            return $cachedData;
        }

        try {
            // Faire la requête à l'API WeatherAPI
            $response = Http::timeout(30)->get("{$this->baseUrl}/forecast.json", [
                'key' => $this->apiKey,
                'q' => $city,
                'days' => $days,
                'aqi' => 'no',
                'alerts' => 'no'
            ]);

            if (!$response->successful()) {
                Log::error("Erreur API WeatherAPI pour {$city}: Status {$response->status()}");
                throw new \Exception("Erreur lors de la récupération des données météo");
            }

            $weatherData = $response->json();
            
            if (!isset($weatherData['forecast']['forecastday'])) {
                throw new \Exception("Format de réponse invalide de l'API météo");
            }

            // Calculer l'humidité moyenne par jour
            $processedData = [
                'city' => $city,
                'days' => $days,
                'forecast' => $weatherData['forecast']['forecastday'],
                'daily_humidity' => $this->calculateDailyAverageHumidity($weatherData['forecast']['forecastday']),
                'retrieved_at' => now()->toISOString()
            ];

            // Mettre en cache pour 2 heures
            Cache::put($cacheKey, $processedData, now()->addMinutes($this->cacheDuration));
            
            Log::info("Données météo récupérées et mises en cache pour {$city}");
            
            return $processedData;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de la météo pour {$city}: " . $e->getMessage());
            throw $e;
        }
    }

    public function calculateDailyAverageHumidity(array $forecastData): array
    {
        $dailyHumidity = [];

        foreach ($forecastData as $day) {
            $date = $day['date'];
            $hourlyData = $day['hour'] ?? [];
            
            if (empty($hourlyData)) {
                // Si pas de données horaires, utiliser la moyenne du jour si disponible
                $dailyHumidity[$date] = $day['day']['avghumidity'] ?? null;
                continue;
            }

            // Calculer la moyenne des humidités horaires
            $totalHumidity = 0;
            $validHours = 0;

            foreach ($hourlyData as $hour) {
                if (isset($hour['humidity'])) {
                    $totalHumidity += $hour['humidity'];
                    $validHours++;
                }
            }

            $averageHumidity = $validHours > 0 ? round($totalHumidity / $validHours, 1) : null;
            $dailyHumidity[$date] = $averageHumidity;
        }

        return $dailyHumidity;
    }

    public function determineForecastDays(array $wateringBenchmark): int
    {
        if (!isset($wateringBenchmark['value']) || !isset($wateringBenchmark['unit'])) {
            return $this->maxForecastDays; // Par défaut, 5 jours
        }

        $unit = strtolower($wateringBenchmark['unit']);
        $value = $wateringBenchmark['value'];

        // Si l'unité n'est pas en jours, retourner la valeur par défaut
        if ($unit !== 'days') {
            return $this->maxForecastDays;
        }

        // Nettoyer la valeur (enlever les guillemets et espaces)
        $cleanValue = trim($value, '"');
        
        // Gérer les cas comme "6-12", "7", etc.
        if (strpos($cleanValue, '-') !== false) {
            // Cas d'une plage comme "6-12"
            $range = explode('-', $cleanValue);
            $maxDays = intval(trim($range[1] ?? $range[0]));
        } else {
            // Cas d'une valeur unique comme "7"
            $maxDays = intval($cleanValue);
        }

        // Limiter au maximum autorisé par l'API (5 jours)
        return min($maxDays, $this->maxForecastDays);
    }
}