<?php

use Illuminate\Database\Seeder;
use App\Models\ProductionOrigin;
use Illuminate\Support\Facades\DB;

class ProductionOriginUpdateSeeder extends Seeder
{
    private $toKeep = [
        [
            'name' => 'Manufacture des Gobelins',
            'label' => 'Création de tapisserie, technique de point plat sur métier de haute lice',
            'label_md' => 'Création de tapisserie, technique de point plat sur métier de haute lice',
            'mapping_key' => 'gobelins',
        ],
        [
            'name' => 'Manufacture de Beauvais',
            'label' => 'Création de tapisserie, technique de point plat sur métier de basse lice',
            'label_md' => 'Création de tapisserie, technique de point plat sur métier de basse lice',
            'mapping_key' => 'beauvais',
        ],
        [
            'name' => 'Manufacture de la Savonnerie',
            'label' => 'Création de tapis, technique du point noué sur métier de haute lice',
            'label_md' => 'Création de tapis, technique du point noué sur métier de haute lice',
            'mapping_key' => 'savonnerie',
        ],
        [
            'name' => 'Atelier Le-Puy-en-Velay',
            'label' => 'Création de dentelle, technique aux fuseaux',
            'label_md' => 'Création de dentelle, technique aux fuseaux',
            'mapping_key' => 'puy-en-velay',
        ],
        [
            'name' => "Atelier d'Alençon",
            'label' => "Création de dentelle et broderie, technique à l'aiguille",
            'label_md' => "Création de dentelle et broderie, technique à l'aiguille",
            'mapping_key' => 'alencon',
        ],
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
        // Supprimer tout sauf ceux à garder
        $namesToKeep = array_column($this->toKeep, 'name');
        DB::table('production_origins')->whereNotIn('name', $namesToKeep)->delete();

        // Upsert les lignes à garder
        foreach ($this->toKeep as $item) {
            ProductionOrigin::updateOrCreate(
                ['name' => $item['name']],
                [
                    'label' => $item['label'],
                    'label_md' => $item['label_md'],
                    'mapping_key' => $item['mapping_key'] ?? null,
                ]
            );
        }
    }
}
