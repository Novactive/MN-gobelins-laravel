<?php

namespace App\Console\Commands;

use App\Jobs\ImportObjectJob;
use App\Jobs\ImportPersonJob;
use App\Services\XmlDataProcessor;
use App\Services\ZetcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportZetcomData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gobelins:import:zetcom {--module= : Import a specific table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private ZetcomService $zetcomService;
    private XmlDataProcessor $dataProcessor;

    /**
     * Create a new command instance.
     *
     * @return void
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
        $moduleName = ucfirst(strtolower($this->option('module')));

        if ($moduleName) {
            $this->importModule($moduleName);
        } else {
            $this->importAllModules();
        }
    }


    public function importModule($moduleName)
    {
        $modulesXml = $this->zetcomService->getModifiedModules($moduleName);

//        Object de teste : de test : 43245 4532 37261 1329(IA)
//        $module = $this->zetcomService->getSingleModule($moduleName,1329);

        if ($moduleName == 'Object') {
            $objects = $this->dataProcessor->processObjectsData($modulesXml);
            foreach ($objects as $object) {
                $this->info("Envoi de l'objet $moduleName (" . $object['id'] .") à la queue");
                ImportObjectJob::dispatch($object);
            }
        } elseif ($moduleName == 'Person') {
            $objects = $this->dataProcessor->processPersonsData($modulesXml);
            foreach ($objects as $object) {
                $this->info("Envoi de l'objet $moduleName (". $object['id'] .") à la queue");
                ImportPersonJob::dispatch($object);
            }
        }

    }


    //TODO ajout d une fonction pour un import global (une transaction): qui fait appel au imports partiels

    public function importAllModules()
    {
        $this->importModule('Object');
        //autre modules
    }
}
