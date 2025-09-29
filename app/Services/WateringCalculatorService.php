<?php
// filepath: app/Services/WateringCalculatorService.php

namespace App\Services;

use App\Contracts\WateringCalculatorServiceInterface;
use Illuminate\Support\Facades\Log;

class WateringCalculatorService implements WateringCalculatorServiceInterface
{
    public function calculateNextWatering(array $wateringBenchmark, array $dailyHumidity): array
    {
        // 1. Extraire le nombre de jours moyen du benchmark
        $baseDays = $this->extractAverageDays($wateringBenchmark);
        
        // 2. Calculer l'humidité moyenne sur la période
        $averageHumidity = $this->calculateAverageHumidity($dailyHumidity);
        
        // 3. Calculer l'ajustement basé sur l'humidité
        $humidityAdjustment = $this->calculateHumidityAdjustment($averageHumidity);
        
        // 4. Appliquer l'ajustement
        $adjustedDays = $baseDays * $humidityAdjustment;
        
        // 5. Convertir en jours et heures
        $timeBreakdown = $this->convertTosDaysAndHours($adjustedDays);
        
        Log::info("Calcul d'arrosage effectué", [
            'base_days' => $baseDays,
            'average_humidity' => $averageHumidity,
            'humidity_adjustment' => $humidityAdjustment,
            'adjusted_days' => $adjustedDays,
            'result' => $timeBreakdown
        ]);
        
        return [
            'base_watering_days' => $baseDays,
            'average_humidity' => $averageHumidity,
            'humidity_adjustment_factor' => $humidityAdjustment,
            'adjusted_total_days' => $adjustedDays,
            'days' => $timeBreakdown['days'],
            'hours' => $timeBreakdown['hours'],
            'total_hours' => $timeBreakdown['total_hours'],
            'message' => $this->generateWateringMessage($timeBreakdown),
            'humidity_explanation' => $this->generateHumidityExplanation($averageHumidity, $humidityAdjustment)
        ];
    }

    public function calculateHumidityAdjustment(float $averageHumidity): float
    {
        if ($averageHumidity > 70) {
            // Au-dessus de 70% : ajouter 10% par tranche de 10%
            $excessHumidity = $averageHumidity - 70;
            $tranches = floor($excessHumidity / 10);
            $adjustment = 1 + ($tranches * 0.10); // +10% par tranche
            
        } elseif ($averageHumidity < 40) {
            // En dessous de 40% : retirer 10% par tranche de 10%
            $deficitHumidity = 40 - $averageHumidity;
            $tranches = floor($deficitHumidity / 10);
            $adjustment = 1 - ($tranches * 0.10); // -10% par tranche
            // S'assurer qu'on ne descend pas en dessous de 0.3 (30% du temps minimum)
            $adjustment = max($adjustment, 0.3);
            
        } else {
            // Entre 40% et 70% : aucun ajustement
            $adjustment = 1.0;
        }
        
        return round($adjustment, 2);
    }

    public function extractAverageDays(array $wateringBenchmark): float
    {
        if (!isset($wateringBenchmark['value']) || !isset($wateringBenchmark['unit'])) {
            return 7.0; // Valeur par défaut
        }
        
        $unit = strtolower($wateringBenchmark['unit']);
        if ($unit !== 'days') {
            return 7.0; // Valeur par défaut si l'unité n'est pas en jours
        }
        
        $value = trim($wateringBenchmark['value'], '"');
        
        // Gérer les plages comme "6-12" ou "7-10"
        if (strpos($value, '-') !== false) {
            $range = explode('-', $value);
            $min = floatval(trim($range[0]));
            $max = floatval(trim($range[1] ?? $range[0]));
            return ($min + $max) / 2; // Moyenne de la plage
        }
        
        // Valeur unique comme "7"
        return floatval($value);
    }

    public function convertTosDaysAndHours(float $totalDays): array
    {
        $days = floor($totalDays);
        $remainingHours = ($totalDays - $days) * 24;
        $hours = round($remainingHours);
        
        // Ajuster si les heures arrondies donnent 24h
        if ($hours >= 24) {
            $days += 1;
            $hours = 0;
        }
        
        return [
            'days' => (int) $days,
            'hours' => (int) $hours,
            'total_hours' => round($totalDays * 24),
            'exact_days' => $totalDays
        ];
    }

    private function calculateAverageHumidity(array $dailyHumidity): float
    {
        $validHumidities = array_filter($dailyHumidity, function($humidity) {
            return $humidity !== null && is_numeric($humidity);
        });
        
        if (empty($validHumidities)) {
            return 60.0; // Valeur par défaut si aucune donnée
        }
        
        return round(array_sum($validHumidities) / count($validHumidities), 1);
    }

    private function generateWateringMessage(array $timeBreakdown): string
    {
        $days = $timeBreakdown['days'];
        $hours = $timeBreakdown['hours'];
        
        if ($days === 0) {
            return "Prochain arrosage dans {$hours} heure" . ($hours > 1 ? 's' : '');
        }
        
        if ($hours === 0) {
            return "Prochain arrosage dans {$days} jour" . ($days > 1 ? 's' : '');
        }
        
        return "Prochain arrosage dans {$days} jour" . ($days > 1 ? 's' : '') . " et {$hours} heure" . ($hours > 1 ? 's' : '');
    }

    private function generateHumidityExplanation(float $averageHumidity, float $adjustment): string
    {
        if ($adjustment > 1.0) {
            $increase = round(($adjustment - 1) * 100);
            return "Humidité élevée ({$averageHumidity}%) : temps d'arrosage augmenté de {$increase}%";
        }
        
        if ($adjustment < 1.0) {
            $decrease = round((1 - $adjustment) * 100);
            return "Humidité faible ({$averageHumidity}%) : temps d'arrosage réduit de {$decrease}%";
        }
        
        return "Humidité normale ({$averageHumidity}%) : aucun ajustement nécessaire";
    }
}