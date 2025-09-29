<?php
// filepath: app/Contracts/WeatherServiceInterface.php

namespace App\Contracts;

interface WeatherServiceInterface
{
    /**
     * Récupérer les prévisions météo pour une ville donnée
     *
     * @param string $city Nom de la ville
     * @param int $days Nombre de jours de prévision (max 5)
     * @return array Données météo avec humidité moyenne par jour
     */
    public function getForecast(string $city, int $days = 5): array;

    /**
     * Calculer l'humidité moyenne par jour à partir des données météo
     *
     * @param array $forecastData Données brutes de l'API
     * @return array Humidité moyenne par jour
     */
    public function calculateDailyAverageHumidity(array $forecastData): array;

    /**
     * Déterminer le nombre de jours de prévision basé sur le watering benchmark
     *
     * @param array $wateringBenchmark Benchmark d'arrosage de la plante
     * @return int Nombre de jours à prévoir (max 5)
     */
    public function determineForecastDays(array $wateringBenchmark): int;
}