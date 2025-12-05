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
            $data = ['name' => $item['name']];

            if (isset($item['label'])) {
                $data['label'] = $item['label'];
            }
            if (isset($item['label_md'])) {
                $data['label_md'] = $item['label_md'];
            }
            ProductionOrigin::updateOrCreate(
                ['mapping_key' => $item['mapping_key']],
                $data
            );
        }

        // Update label and label_md
        $data = [
            [
                'mapping_key' => 'gobelins',
                'label' => 'Manufacture des Gobelins',
                'label_md' => 'Création de tapisserie, technique de point plat sur métier de haute lice',
            ],
            [
                'mapping_key' => 'beauvais',
                'label' => 'Manufacture de Beauvais',
                'label_md' => 'Création de tapisserie, technique de point plat sur métier de basse lice',
            ],
            [
                'mapping_key' => 'savonnerie',
                'label' => 'Manufacture de la Savonnerie',
                'label_md' => 'Création de tapis, technique du point noué sur métier de haute lice',
            ],
            [
                'mapping_key' => 'puy-en-velay',
                'label' => 'Atelier Le-Puy-en-Velay',
                'label_md' => 'Création de dentelle, technique aux fuseaux',
            ],
            [
                'mapping_key' => 'alencon',
                'label' => "Atelier d'Alençon",
                'label_md' => "Création de dentelle et broderie, technique à l'aiguille",
            ],
            [
                'mapping_key' => 'arc',
                'label' => 'ARC',
                'label_md' => 'Atelier de recherche et création de mobilier',
            ],
            [
                'mapping_key' => 'sevres',
                'label' => 'Manufacture de Sèvres',
                'label_md' => "Création d'œuvres et objets d'art en porcelaine",
            ],
        ];

        foreach ($data as $item) {
            ProductionOrigin::where('mapping_key', $item['mapping_key'])
                ->update([
                    'label' => $item['label'],
                    'label_md' => $item['label_md'],
                ]);
        }
    }
}
