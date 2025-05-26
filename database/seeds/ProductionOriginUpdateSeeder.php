<?php

use Illuminate\Database\Seeder;
use App\Models\ProductionOrigin;

class ProductionOriginUpdateSeeder extends Seeder
{

    private $toUpdateOrAdd = [
        [
            'name' => 'ARC',
            'label' => 'Atelier de recherche et création de mobilier',
            'label_md' => 'Atelier de recherche et création de mobilier',
            'mapping_key' => 'arc',
        ],
        [
            'name' => 'Manufacture de Sèvres',
            'label' => "Création d'œuvres et objets d'art en porcelaine",
            'label_md' => "Création d'œuvres et objets d'art en porcelaine",
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
                [
                    'name' => $item['name'],
                    'label' => $item['label'],
                    'label_md' => $item['label_md']
                ]
            );
        }

    }
}
