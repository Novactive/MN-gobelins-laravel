<?php

namespace App\Jobs;

use App\Services\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ImportObjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $object;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $import = \app(Import::class);
        $import->execute($this->object);
    }
}
