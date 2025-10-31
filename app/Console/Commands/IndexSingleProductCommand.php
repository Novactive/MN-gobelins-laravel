<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IndexSingleProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:index-single-product {product_id?} {--all} {--force} {--published-only} {--chunk=100} {--remove-unpublished}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexe un seul produit ou tous les produits dans Elasticsearch';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $all = $this->option('all');
        $force = $this->option('force');
        $publishedOnly = $this->option('published-only');
        $removeUnpublished = $this->option('remove-unpublished');
        $chunkSize = (int) $this->option('chunk');

        if ($all) {
            return $this->indexAllProducts($force, $publishedOnly, $removeUnpublished, $chunkSize);
        } else {
            $productId = $this->argument('product_id');
            if (!$productId) {
                $this->error("❌ ID du produit requis ou utilisez --all pour indexer tous les produits");
                return 1;
            }
            return $this->indexSingleProduct((int) $productId, $force, $removeUnpublished);
        }
    }

    /**
     * Indexe un seul produit
     */
    private function indexSingleProduct(int $productId, bool $force, bool $removeUnpublished): int
    {
        $this->info("=== Indexation du produit ID: $productId ===");

        // 1. Trouver le produit
        $product = Product::with([
            'authors', 'productType', 'images', 'style',
            'materials', 'productionOrigin', 'entryMode', 'period'
        ])->find($productId);

        if (!$product) {
            $this->error("❌ Produit non trouvé avec l'ID: $productId");
            return 1;
        }

        $this->info("✅ Produit trouvé:");
        $this->info("   - Inventory ID: {$product->inventory_id}");
        $this->info("   - Titre: {$product->title_or_designation}");
        $this->info("   - Publié: " . ($product->is_published ? 'Oui' : 'Non'));

        // 2. Gérer les produits non publiés
        if (!$product->is_published) {

            if ($removeUnpublished) {
                // Supprimer le produit de l'index Elasticsearch
                $this->info("🗑️  Suppression du produit non publié de l'index...");

                try {
                    $product->unsearchable();
                    $this->info("✅ Produit supprimé de l'index Elasticsearch avec succès!");
                    
                    // Log de la suppression
                    Log::info("Produit {$product->inventory_id} (ID: $productId) supprimé de l'index Elasticsearch car non publié");
                    
                } catch (\Exception $e) {
                    $this->error("❌ Erreur lors de la suppression: " . $e->getMessage());
                    Log::error("Erreur de suppression du produit $productId", [
                        'product_id' => $productId,
                        'inventory_id' => $product->inventory_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return 1;
                }
                
                $this->info("=== Suppression terminée ===");
                return 0;
            } elseif (!$force) {
                $this->warn("⚠️  Le produit n'est pas publié (is_published = false)");
                $this->warn("   Utilisez --force pour forcer l'indexation");
                $this->warn("   Utilisez --remove-unpublished pour supprimer de l'index");
                return 1;
            } else {
                $this->warn("⚠️  Indexation forcée d'un produit non publié");
            }
        }

        // 3. Indexer le produit
        $this->info("🔄 Indexation en cours...");

        try {
            $product->searchable();
            $this->info("✅ Indexation réussie!");

            // Log du succès
            Log::info("Produit {$product->inventory_id} (ID: $productId) indexé avec succès dans Elasticsearch");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'indexation: " . $e->getMessage());
            Log::error("Erreur d'indexation du produit $productId", [
                'product_id' => $productId,
                'inventory_id' => $product->inventory_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $this->info("=== Indexation terminée ===");
        return 0;
    }

    /**
     * Indexe tous les produits un par un
     */
    private function indexAllProducts(bool $force, bool $publishedOnly, bool $removeUnpublished, int $chunkSize): int
    {
        $this->info("=== Indexation de tous les produits ===");
        $this->info("Force: " . ($force ? 'Oui' : 'Non'));
        $this->info("Publiés seulement: " . ($publishedOnly ? 'Oui' : 'Non'));
        $this->info("Supprimer non publiés: " . ($removeUnpublished ? 'Oui' : 'Non'));
        $this->info("Taille des lots: $chunkSize");

        // Construire la requête
        $query = Product::with([
            'authors', 'productType', 'images', 'style',
            'materials', 'productionOrigin', 'entryMode', 'period'
        ]);
        //$query = Product::query();

        if ($publishedOnly && !$force) {
            $query->where('is_published', true);
            $this->info("Filtrage: produits publiés seulement");
        }

        $totalProducts = $query->count();
        $this->info("Total des produits à traiter: $totalProducts");

        if ($totalProducts === 0) {
            $this->warn("Aucun produit à indexer.");
            return 0;
        }

        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Traitement par lots
        $query->orderBy('id')->chunk($chunkSize, function ($products) use (
            $progressBar,
            $force,
            $removeUnpublished,
            &$successCount,
            &$errorCount,
            &$errors
        ) {
            foreach ($products as $product) {
                try {
                    // Gérer les produits non publiés
                    if (!$product->is_published) {
                        if ($removeUnpublished) {
                            // Supprimer le produit de l'index
                            $product->unsearchable();
                            $this->line("\n🗑️  Produit {$product->inventory_id} (ID: {$product->id}) supprimé de l'index (non publié)");
                            $successCount++;
                        } elseif (!$force) {
                            $this->line("\n⚠️  Produit {$product->inventory_id} (ID: {$product->id}) non publié - ignoré");
                        } else {
                            // Indexer le produit même s'il n'est pas publié (force)
                            $product->searchable();
                            $successCount++;
                            $this->line("\n✅ Produit {$product->inventory_id} (ID: {$product->id}) indexé (forcé)");
                        }
                        $progressBar->advance();
                        continue;
                    }

                    // Indexer le produit publié
                    $product->searchable();
                    $successCount++;

                    $this->line("\n✅ Produit {$product->inventory_id} (ID: {$product->id}) indexé");

                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Erreur lors de l'indexation du produit {$product->inventory_id} (ID: {$product->id}): " . $e->getMessage();

                    $this->error("\n❌ " . $errorMessage);
                    Log::error($errorMessage, [
                        'product_id' => $product->id,
                        'inventory_id' => $product->inventory_id,
                        'error' => $e->getMessage()
                    ]);

                    $errors[] = [
                        'id' => $product->id,
                        'inventory_id' => $product->inventory_id,
                        'error' => $e->getMessage()
                    ];
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("=== RÉSUMÉ DE L'INDEXATION ===");
        $this->info("Produits indexés avec succès: $successCount");
        $this->info("Produits en erreur: $errorCount");
        $this->info("Taux de succès: " . round(($successCount / $totalProducts) * 100, 2) . "%");

        if (!empty($errors)) {
            $this->error("=== ERREURS DÉTAILLÉES ===");
            foreach (array_slice($errors, 0, 10) as $error) { // Limiter à 10 erreurs
                $this->error("ID: {$error['id']} - {$error['inventory_id']}: {$error['error']}");
            }
            if (count($errors) > 10) {
                $this->error("... et " . (count($errors) - 10) . " autres erreurs");
            }
        }

        return $errorCount === 0 ? 0 : 1;
    }
}