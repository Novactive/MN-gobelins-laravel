<?php

namespace App\Console\Commands;

use App\Services\ZetcomService;
use App\Services\XmlDataProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ImportMultimediaData extends Command
{
    const MODULE_NAME = "Multimedia";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gobelins:import:multimedia {--offset : Point de départ} {--limit= : Nombre d\'objets à traiter} {--force : Forcer le téléchargement même des images non publiables} {--image-id= : ID spécifique de l\'image à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe les données Multimedia depuis Zetcom et télécharge les images';

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $offset = (int)$this->option('offset');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;
        $force = $this->option('force');
        $imageId = $this->option('image-id');

        $this->info("=== Import des données Multimedia ===");
        if ($imageId) {
            $this->info("Image ID spécifique: $imageId");
        } else {
            $this->info("Offset: $offset, Limit: $limit");
        }
        if ($force) {
            $this->info("Mode FORCE activé (téléchargement de toutes les images)");
        }
        $this->info("");

        try {
            $this->importMultimediaObjects($offset, $limit, $force, $imageId);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("ERREUR: " . $e->getMessage());
            Log::error("Erreur lors de l'import Multimedia: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @param $offset
     * @param $limit
     * @param bool $force
     * @param string|null $imageId
     * @return void
     * @throws \Exception
     */
    private function importMultimediaObjects($offset = null, $limit = null, bool $force = false, ?string $imageId = null)
    {
        $this->info("1. Récupération des objets Multimedia...");

        if ($imageId) {
            $this->info("   Récupération de l'image spécifique: $imageId");
            $modulesXml = $this->zetcomService->getSingleModule('Multimedia', (int)$imageId);
            $multimediaItems = $this->dataProcessor->parseXml($modulesXml);
        } else {
            $this->info("   Utilisation du filtrage API (images publiables uniquement)");
            $modulesXml = $this->zetcomService->getPublishableMultimediaModules(true, $limit, $offset);
            $multimediaItems = $this->dataProcessor->parseXml($modulesXml);
        }

        if (empty($multimediaItems)) {
            $this->warn("Aucun objet Multimedia trouvé.");
            return;
        }

        $this->info( count($multimediaItems) . " objets Multimedia trouvés");

        $this->info("2. Extraction des IDs des objets liés...");
        $objectIds = $this->extractLinkedObjectIds($multimediaItems);

        if (empty($objectIds)) {
            $this->warn("Aucun objet lié trouvé.");
            return;
        }

        $this->info( count($objectIds) . " objets liés trouvés");
        $this->info("   IDs: " . implode(', ', array_slice($objectIds, 0, 10)) .
            (count($objectIds) > 10 ? '...' : ''));
        $this->info("");

        // Exécuter la commande d'import des objets
        $this->info("3. Import des objets liés...");
        $this->importLinkedObjects($objectIds, $force);

        $this->info("");
        $this->info("=== TERMINÉ ===");
    }

    /**
     * Extrait les IDs des objets liés aux images
     * @param array $multimediaItems
     * @return array
     */
    private function extractLinkedObjectIds(array $multimediaItems): array
    {
        $objectIds = [];

        foreach ($multimediaItems as $item) {
            $associatedObjectId = $this->extractAssociatedObjectId($item);

            if ($associatedObjectId) {
                $objectIds[] = $associatedObjectId;
            }
        }

        return array_unique($objectIds);
    }

    /**
     * Importe les objets liés via la commande gobelins:import:zetcom
     * @param array $objectIds
     * @param bool $force
     * @return void
     */
    private function importLinkedObjects(array $objectIds, bool $force = false): void
    {
        $chunkSize = 50;
        $chunks = array_chunk($objectIds, $chunkSize);

        foreach ($chunks as $index => $chunk) {
            $this->info("   Traitement du lot " . ($index + 1) . "/" . count($chunks) . " (" . count($chunk) . " objets)");

            $objectIdsString = implode(',', $chunk);
            $command = "php artisan gobelins:import:zetcom --all --limit=10 --offset=0 --objectIds={$objectIdsString}";

            if ($force) {
                $command .= " --skip-image-download";
            }
            $this->info("   Commande: {$command}");
            $exitCode = 0;
            $output = [];
            exec($command . ' 2>&1', $output, $exitCode);

            if ($exitCode === 0) {
                $this->info("Lot " . ($index + 1) . " traité avec succès");
            } else {
                $this->error("Erreur lors du traitement du lot " . ($index + 1));
                $this->error("Sortie: " . implode("\n", $output));
            }

            $this->info("");
        }
    }


    /**
     * Extrait l'ID de l'objet associé
     * @param \SimpleXMLElement $item
     * @return int|null
     */
    private function extractAssociatedObjectId($item): ?int
    {
        $item->registerXPathNamespace('zetcom', $this->dataProcessor->getModuleNamespace());
        $results = $item->xpath('./zetcom:moduleReference[@name="MulObjectRef"]/zetcom:moduleReferenceItem/@moduleItemId');

        if ($results && isset($results[0])) {
            return (int) $results[0];
        }

        $results = $item->xpath('.//zetcom:moduleReference[@name="MulObjectRef"]/zetcom:moduleReferenceItem/@moduleItemId');

        return $results && isset($results[0]) ? (int) $results[0] : null;
    }
} 