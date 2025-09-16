<?php

namespace App\Console\Commands;

use App\Services\PlantService;
use Illuminate\Console\Command;

class FetchPlants extends Command
{
    protected $signature = 'plants:fetch 
                            {--limit=100 : Nombre maximum de requêtes à effectuer}
                            {--reset : Réinitialiser la progression}
                            {--stats : Afficher uniquement les statistiques}';
                            
    protected $description = 'Récupérer les plantes depuis l\'API Perenual avec gestion de progression automatique';

    public function handle(PlantService $plantService): int
    {
        if ($this->option('reset')) {
            $plantService->resetProgress();
            $this->info('✅ Progression réinitialisée');
            return Command::SUCCESS;
        }

        if ($this->option('stats')) {
            $stats = $plantService->getStats();
            $this->displayStats($stats);
            return Command::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        
        $this->info("🌱 Début de la synchronisation des plantes (limite: {$limit})");
        $this->newLine();

        $stats = $plantService->fetchAndStorePlants($limit);

        $this->displayResults($stats);

        return Command::SUCCESS;
    }

    private function displayResults(array $stats): void
    {
        $this->info('📊 Résultats de la synchronisation:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['IDs traités', "{$stats['starting_id']} → {$stats['ending_id']}"],
                ['Plantes traitées', $stats['processed']],
                ['Nouvelles plantes', $stats['created']],
                ['Plantes mises à jour', $stats['updated']],
                ['Erreurs', $stats['errors']],
                ['Requêtes aujourd\'hui', $stats['total_requests_today']],
            ]
        );
    }

    private function displayStats(array $stats): void
    {
        $this->info('📈 Statistiques actuelles:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Dernier ID traité', $stats['last_processed_id']],
                ['Requêtes aujourd\'hui', "{$stats['daily_request_count']}/{$stats['daily_limit']}"],
                ['Requêtes restantes', $stats['remaining_requests']],
                ['Succès aujourd\'hui', $stats['successful_requests']],
                ['Erreurs aujourd\'hui', $stats['failed_requests']],
                ['Total plantes en BDD', $stats['total_plants_in_db']],
                ['Dernière sync', $stats['last_sync_date']],
            ]
        );
    }
}