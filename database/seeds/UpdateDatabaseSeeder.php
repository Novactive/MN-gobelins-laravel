<?php

use Illuminate\Database\Seeder;

class UpdateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ProductTypesUpdateSeeder::class,
            MaterialUpdateSeeder::class,
            ProductionOriginUpdateSeeder::class,
            RenameDirectoireStyleSeeder::class
        ]);
    }
}
