<?php

namespace App\Contracts;

interface WateringCalculatorServiceInterface
{
    /**
     * Calculer le temps avant le prochain arrosage
     *
     * @param array $wateringBenchmark Benchmark d'arrosage de la plante
     * @param array $dailyHumidity Humidité moyenne par jour
     * @return array Résultat du calcul avec jours, heures et détails
     */
    public function calculateNextWatering(array $wateringBenchmark, array $dailyHumidity): array;

    /**
     * Calculer l'ajustement basé sur l'humidité moyenne
     *
     * @param float $averageHumidity Humidité moyenne en pourcentage
     * @return float Facteur d'ajustement (1.0 = aucun changement)
     */
    public function calculateHumidityAdjustment(float $averageHumidity): float;

    /**
     * Extraire la valeur moyenne du watering benchmark
     *
     * @param array $wateringBenchmark Benchmark d'arrosage
     * @return float Nombre de jours moyen
     */
    public function extractAverageDays(array $wateringBenchmark): float;

    /**
     * Convertir les jours décimaux en jours et heures
     *
     * @param float $totalDays Nombre total de jours (décimal)
     * @return array Jours et heures séparés
     */
    public function convertTosDaysAndHours(float $totalDays): array;
}