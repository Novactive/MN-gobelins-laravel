<?php

namespace App\Console\Commands;

use App\Jobs\ImportObjectJob;
use App\Services\XmlDataProcessor;
use App\Services\ZetcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportZetcomData extends Command
{
    const MODULE_NAME = "Object";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gobelins:import:zetcom {--all : Import globale} {--offset=} {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
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
        $this->importObjects();

        return Command::SUCCESS;
    }


    /**
     * @return int|void
     * @throws \Exception
     */
    public function importObjects()
    {
        $all = (bool)$this->option('all');
        $limit = $all ? (int)$this->option('limit') : null;
        $offset = $all ? (int)$this->option('offset') : 0;

        if ($all && !$limit) {
            $this->error("Importer tous les produits sans limite et offset n'est pas faisable !");
            $this->info("Veuillez spécifier --limit (ex: --limit=1000). L'offset est par defaut 0, vous pouvez l'incrémenter à chaque itération (ex 2eme itér: --offset=1000)");
            return Command::FAILURE;
        }
        //module pour teste
        //$modulesXml = $this->zetcomService->getSingleModule('Multimedia', 637457);

        $queueName = env('RABBITMQ_QUEUE', 'default');
        $modulesXml = $this->zetcomService->getModifiedModules(self::MODULE_NAME, $all, $limit, $offset);
        $objects = $this->dataProcessor->processObjectsData($modulesXml);

        foreach ($objects as $object) {
            $this->info("Envoi de l'objet " . self::MODULE_NAME . " (" . $object['id'] . ") à la queue");
            Log::info("Envoi de l'objet " . self::MODULE_NAME . " (" . $object['id'] . ") à la queue");

            $job = new ImportObjectJob($object);
            dispatch($job)->onConnection('rabbitmq')->onQueue($queueName);
        }

        $job = new ImportObjectJob(['id' => 0]);
        dispatch($job)->onConnection('rabbitmq')->onQueue($queueName);
    }


}
