<?php

use Illuminate\Database\Seeder;
use App\Models\ProductionOrigin;

class ProductionOriginUpdateSeeder extends Seeder
{

    private $toUpdateOrAdd = [
        [
            'name' => 'Atelier de dentelle du Puy-en-Velay',
            'mapping_key' => 'puy-en-velay',
        ],
        [
            'name' => "Atelier de dentelle d'Alençon",
            'mapping_key' => 'alencon',
        ],
        [
            'name' => 'Atelier de Recherche et de Création (ARC)',
            'mapping_key' => 'arc',
        ],
        [
            'name' => 'Manufacture de Sèvres',
            'mapping_key' => 'sevres',
        ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        foreach ($this->toUpdateOrAdd as $item) {
            ProductionOrigin::updateOrCreate(
                ['mapping_key' => $item['mapping_key']],
                ['name' => $item['name']]
            );
        }

    }
}
