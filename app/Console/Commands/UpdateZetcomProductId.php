<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\XmlDataProcessor;
use App\Services\ZetcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateZetcomProductId extends Command
{
    const MODULE_NAME = "Object";
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gobelins:update:zetcom-product-id {--all : Traiter tous les objets} {--offset=0 : Offset pour commencer} {--limit=100 : Limite du nombre d\'objets à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour le champ zetcom_product_id pour les produits existants';

    private ZetcomService $zetcomService;
    private XmlDataProcessor $dataProcessor;

    /**
     * @param ZetcomService $zetcomService
     * @param XmlDataProcessor $dataProcessor
     */
    public function __construct(ZetcomService $zetcomService, XmlDataProcessor $dataProcessor)
    {
        parent::__construct();
        $this->zetcomService = $zetcomService;
        $this->dataProcessor = $dataProcessor;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $this->updateZetcomProductIds();
        return Command::SUCCESS;
    }

    /**
     * @return int|void
     * @throws \Exception
     */
    public function updateZetcomProductIds()
    {
        $all = (bool)$this->option('all');
        $limit = $all ? (int)$this->option('limit') : null;
        $offset = $all ? (int)$this->option('offset') : 0;

        if ($all && !$limit) {
            $this->error("Traiter tous les objets sans limite n'est pas faisable !");
            $this->info("Veuillez spécifier --limit (ex: --limit=1000). L'offset est par défaut 0, vous pouvez l'incrémenter à chaque itération (ex 2eme itér: --offset=1000)");
            return Command::FAILURE;
        }

        if (!$all && $limit <= 0) {
          //  $this->error("La limite doit être supérieure à 0 !");
            return Command::FAILURE;
        }

        $this->info("Début de la mise à jour des zetcom_product_id avec offset=$offset et limit=" . ($limit ?? 'illimité'));

        try {
            // Récupérer les objets modifiés depuis Zetcom
            $modulesXml = $this->zetcomService->getModifiedModules(self::MODULE_NAME, $all, $limit, $offset);
            $objects = $this->dataProcessor->processObjectsData($modulesXml);

            if (empty($objects)) {
                $this->info("Aucun objet trouvé pour la mise à jour.");
                return Command::SUCCESS;
            }

           // $this->info("Traitement de " . count($objects) . " objets...");

            $updatedCount = 0;
            $errorCount = 0;
            $notFoundIds = [];

            foreach ($objects as $object) {
                try {
                    $inventoryId = $object['inventory_root'] . '-' . 
                        ((!isset($object['inventory_number']) || $object['inventory_number'] == 0) ? "000" : $object['inventory_number']) . '-' .
                        ((!isset($object['inventory_suffix']) || $object['inventory_suffix'] == 0) ? "000" : $object['inventory_suffix']) . 
                        ($object['inventory_suffix2'] ? "-" . $object['inventory_suffix2'] : '');

                    // Chercher le produit par inventory_id
                    $product = Product::where('inventory_id', $inventoryId)->first();

                    if ($product) {
                        // Mettre à jour uniquement le champ zetcom_product_id
                        $product->zetcom_product_id = (int)$object['id'];
                        $product->save();

                        //$this->info("✓ Produit $inventoryId mis à jour avec zetcom_product_id = " . $object['id']);
                        $updatedCount++;
                    } else {
                       // $this->warn("⚠ Produit $inventoryId non trouvé dans la base de données objectId=".$object['id']);
                        $notFoundIds[] = $object['id'];
                    }

                } catch (\Exception $e) {
                   // $this->error("✗ Erreur lors du traitement de l'objet " . $object['id'] . ": " . $e->getMessage());
                  //  Log::error("Erreur mise à jour zetcom_product_id pour l'objet " . $object['id'] . ": " . $e->getMessage());
                    $errorCount++;
                }
            }

           // $this->info("Mise à jour terminée :");
           // $this->info("- Produits mis à jour : $updatedCount");
           // $this->info("- Erreurs : $errorCount");
            
            if (!empty($notFoundIds)) {
                $notFoundIdsString = implode(',', $notFoundIds);
                $this->info("- Objets non trouvés (" . count($notFoundIds) . ") : $notFoundIdsString");
            }

        } catch (\Exception $e) {
          //  $this->error("Erreur lors de la récupération des données Zetcom : " . $e->getMessage());
            //Log::error("Erreur UpdateZetcomProductId : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 