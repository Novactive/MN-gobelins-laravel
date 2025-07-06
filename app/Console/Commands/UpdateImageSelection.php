<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Selection;
use Illuminate\Support\Facades\DB;

class UpdateImageSelection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'selections:update-images {--dry-run : Afficher ce qui serait fait sans exécuter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour la table image_selection en récupérant les 4 premières images des produits de chaque sélection';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('=== MODE DRY RUN - Aucune modification ne sera effectuée ===');
        }

        $this->info('=== Mise à jour de la table image_selection ===');

        try {
            // Compter les enregistrements actuels
            $currentCount = DB::table('image_selection')->count();
            $this->info("1. Enregistrements actuels dans image_selection: {$currentCount}");

            if (!$isDryRun) {
                // Vider la table image_selection pour repartir de zéro
                $this->info('2. Vidage de la table image_selection...');
                DB::table('image_selection')->truncate();
                $this->info('   ✓ Table image_selection vidée');
            }

            // Récupérer toutes les sélections
            $this->info('3. Récupération des sélections...');
            $selections = Selection::all();
            $this->info("   ✓ " . $selections->count() . " sélections trouvées");

            $totalImagesAdded = 0;
            $progressBar = $this->output->createProgressBar($selections->count());
            $progressBar->start();

            foreach ($selections as $selection) {
                // Récupérer les 4 premiers produits de cette sélection
                $products = $selection->products()
                    ->where('is_published', true)
                    ->limit(4)
                    ->get();

                $order = 1;

                foreach ($products as $product) {
                    // Récupérer la première image de ce produit (la plus prioritaire)
                    $image = $product->images()
                        ->where('is_published', true)
                        ->orderBy('is_poster', 'DESC') // Priorité aux images poster
                        ->orderBy('is_prime_quality', 'DESC') // Puis aux images de qualité prime
                        ->orderBy('id', 'ASC') // Puis par ordre d'ID
                        ->first();

                    if ($image) {
                        if (!$isDryRun) {
                            // Insérer dans la table image_selection
                            DB::table('image_selection')->insert([
                                'image_id' => $image->id,
                                'selection_id' => $selection->id,
                                'order' => $order
                            ]);
                        }
                        $totalImagesAdded++;
                        $order++;
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line('');

            // Afficher le résumé
            $this->info('=== RÉSUMÉ ===');
            $this->info("Sélections traitées: " . $selections->count());
            $this->info("Images totales ajoutées: {$totalImagesAdded}");

            if ($isDryRun) {
                $this->warn('Mode dry-run: Aucune modification effectuée');
            } else {
                $this->info('Script terminé avec succès!');
            }

            return 0;

        } catch (Exception $e) {
            $this->error("ERREUR: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile());
            $this->error("Ligne: " . $e->getLine());
            return 1;
        }
    }
} 