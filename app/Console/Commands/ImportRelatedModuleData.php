<?php

namespace App\Console\Commands;

use App\Services\ZetcomService;
use App\Services\XmlDataProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ImportRelatedModuleData extends Command
{
    const MODULE_NAME = "Multimedia";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gobelins:import:related {--offset : Point de départ} {--limit= : Nombre d\'objets à traiter} {--types= : Liste des types (ex: multimedia,person,conservation,literature)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupère des modules (Multimedia, Person, Literature, Conservation), extrait les Objects liés, et lance leur import.';

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
        $typesOption = $this->option('types');

        // Types autorisés et ordre par défaut
        $allowedTypes = ['multimedia',  'conservation', 'person', 'literature'];

        // Parser l'option --types (séparée par des virgules). Si vide ou non fournie, prendre tous les types par défaut
        $types = [];
        if ($typesOption === null || trim((string)$typesOption) === '') {
            $types = $allowedTypes;
        } else {
            $types = array_filter(array_map(function ($t) { return strtolower(trim($t)); }, explode(',', (string)$typesOption)));
            // Ne garder que les types valides, et retirer doublons tout en préservant l'ordre saisi
            $types = array_values(array_unique(array_values(array_intersect($types, $allowedTypes))));
            if (empty($types)) {
                $types = $allowedTypes;
            }
        }

        $this->info("=== Import des données Zetcom (types: " . implode(', ', $types) . ") ===");
        $this->info("Offset: $offset, Limit: $limit");
        $this->info("");

        try {
            // Lancer l'import pour chaque type demandé
            $startDate = now()->subHours(24);
            foreach ($types as $type) {
                $this->importModules($offset, $limit, $type, $startDate);
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("ERREUR: " . $e->getMessage());
            Log::error("Erreur lors de l'import: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @param $offset
     * @param $limit
     * @param string $type
     * @param $startDate
     * @return void
     * @throws \Exception
     */
    private function importModules($offset = null, $limit = null, string $type = 'multimedia', $startDate)
    {
        $this->info("1. Récupération des modules (type: $type)...");

        // PHP 7.4 compatible mapping (no match expression)
        $moduleName = 'Multimedia';
        switch ($type) {
            case 'person':
                $moduleName = 'Person';
                break;
            case 'literature':
                $moduleName = 'Literature';
                break;
            case 'conservation':
                $moduleName = 'Conservation';
                break;
        }

        $this->info("   Récupération des modules modifiés ($moduleName)");
        $modulesXml = $this->zetcomService->getModifiedModules($moduleName, false, $limit, $offset, $startDate);
        $moduleItems = $this->dataProcessor->parseXml($modulesXml);

        if (empty($moduleItems)) {
            $this->warn("Aucun module trouvé pour $moduleName.");
            return;
        }

        $this->info( count($moduleItems) . " modules $moduleName trouvés");

        $this->info("2. Extraction des IDs des objets liés...");
        $objectIds = $this->extractLinkedObjectIds($moduleItems,$moduleName );

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
        $this->importLinkedObjects($objectIds);

        $this->info("");
        $this->info("=== TERMINÉ ===");
    }

    /**
     * @param array $moduleItems
     * @return array
     */
    private function extractLinkedObjectIds(array $moduleItems, $moduleName = null): array
    {
        $objectIds = [];


        foreach ($moduleItems as $item) {
            // Récupérer tous les objets liés (tous les types retournent maintenant un tableau)
            $linkedObjectIds = $this->extractAssociatedObjectId($item, $moduleName);
            $objectIds = array_merge($objectIds, $linkedObjectIds);
        }

        return array_unique($objectIds);
    }

    /**
     * @param array $objectIds
     * @param bool $force
     * @return void
     */
    private function importLinkedObjects(array $objectIds): void
    {
        $chunkSize = 50;
        $chunks = array_chunk($objectIds, $chunkSize);

        foreach ($chunks as $index => $chunk) {
            $this->info("   Traitement du lot " . ($index + 1) . "/" . count($chunks) . " (" . count($chunk) . " objets)");

            $objectIdsString = implode(',', $chunk);
            $command = "php artisan gobelins:import:zetcom --all --limit=10 --offset=0 --objectIds={$objectIdsString}";
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
     * @param \SimpleXMLElement $item
     * @param string $moduleName
     * @return array
     */
    private function extractAssociatedObjectId($item, $moduleName = null): array
    {
        $objectIds = [];
        $item->registerXPathNamespace('zetcom', $this->dataProcessor->getModuleNamespace());

        $results = $item->xpath('./zetcom:moduleReference[contains(@name, "ObjectRef")]/zetcom:moduleReferenceItem/@moduleItemId');

        if ($results && !empty($results)) {
            foreach ($results as $result) {
                // Accéder à l'attribut moduleItemId de l'objet SimpleXMLElement
                $objectIds[] = (int) $result['moduleItemId'];
            }
        } else {
            $results = $item->xpath('.//zetcom:moduleReference[contains(@name, "ObjectRef")]/zetcom:moduleReferenceItem/@moduleItemId');
            if ($results && !empty($results)) {
                foreach ($results as $result) {
                    // Accéder à l'attribut moduleItemId de l'objet SimpleXMLElement
                    $objectIds[] = (int) $result['moduleItemId'];
                }
            }
        }

        return $objectIds;
    }
}


