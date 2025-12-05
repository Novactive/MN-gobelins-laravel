<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;

class CreateIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'els:indices:create {index?}{--connection= : Elasticsearch connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new index';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $host = config('es.host');

        $client = ClientBuilder::create()->setHosts($host)->build();
        $indices = !is_null($this->argument('index')) ?
            [$this->argument('index')] :
            array_keys(config('es.indices'));

        foreach ($indices as $index) {
            $config = config("es.indices.{$index}");

            if (is_null($config)) {
                $this->warn("Missing configuration for index: {$index}");
                continue;
            }

            if ($client->indices()->exists(['index' => $index])) {
                $this->warn("Index {$index} is already exists!");
                $client->indices()->delete(['index' => $index]);
                $this->warn("Delete index {$index}");
            }

            if ($client->indices()->exists(['index' => 'gobelins_search'])) {
                $client->indices()->delete(['index' => 'gobelins_search']);
            }

            // Create index with settings from config file

            $this->info("Creating index: {$index}");

            $client->indices()->create([

                'index' => $index,

                'body' => [
                    "settings" => $config['settings']
                ]

            ]);

            if (isset($config['aliases'])) {
                foreach ($config['aliases'] as $alias) {
                    $this->info("Creating alias: {$alias} for index: {$index}");
                    $client->indices()->updateAliases([
                        "body" => [
                            'actions' => [
                                [
                                    'add' => [
                                        'index' => $index,
                                        'alias' => $alias
                                    ]
                                ]
                            ]
                        ]
                    ]);
                }
            }
            if (isset($config['mappings'])) {
                foreach ($config['mappings'] as $type => $mapping) {
                    // Create mapping for type from config file

                    $this->info("Creating mapping for type: {$type} in index: {$index}");
                    $client->indices()->putMapping([
                        'index' => $index,
                        'type' => $type,
                        'body' => $mapping,
                        "include_type_name" => true
                    ]);
                }
            }
        }
    }
}
