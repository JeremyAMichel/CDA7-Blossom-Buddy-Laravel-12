<?php
// filepath: app/Contracts/PlantServiceInterface.php

namespace App\Contracts;

interface PlantServiceInterface
{
    /**
     * Récupérer et stocker des plantes depuis l'API externe
     *
     * @param int|null $maxRequests Nombre maximum de requêtes à effectuer
     * @return array Statistiques de la synchronisation
     */
    public function fetchAndStorePlants(int $maxRequests = null): array;

    /**
     * Obtenir les statistiques de synchronisation
     *
     * @return array Statistiques actuelles
     */
    public function getStats(): array;

    /**
     * Réinitialiser la progression de synchronisation
     *
     * @return void
     */
    public function resetProgress(): void;
}