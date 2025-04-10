<?php

use App\Models\ProductType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductTypesUpdateSeeder extends Seeder
{

    private $toAdd = [
        'Luminaire > Lampe > Lampe-bouillotte',
        'Meuble > Bureau > Bureau plat',
        'Meuble > Miroir > Miroir à patin',
        'Meuble > Paravent et écran > Écran de cheminée',
        'Meuble > Rangement > Cartonnier',
        'Meuble > Table > Table de décharge',
        'Meuble > Table > Table de nuit',
        'Meuble > Table > Table de salle à manger',
        'Objet décoratif > Art de la table > Surtout',
        'Objet décoratif > Horloge',
        'Siège > Chaise > Chaise ajourée',
        'Siège > Chaise > Chaise légère',
        'Siège > Fauteuil > Fauteuil gondole',
        ];

    private $toRemove = [
        'Luminaire > Chandelier > Chandelier',
        'Luminaire > Lampe > Lampe',
        'Meuble > Bureau > Bureau',
        'Meuble > Bureau > Pupitre',
        'Meuble > Bureau > Bureau à cylindre',
        'Meuble > Lit > Lit',
        'Meuble > Miroir > Miroir',
        'Meuble > Paravent et écran > Écran à feu',
        'Meuble > Rangement > Semainier',
        'Meuble > Table > Table',
        'Meuble > Table > Table de chevet',
        'Meuble > Table > Table de salon',
        'Objet décoratif > Art de la table > Plat',
        'Objet décoratif > Horlogerie > Horloge',
        'Objet décoratif > Horlogerie > Pendule',
        'Objet décoratif > Horlogerie',
        'Siège > Canapé > Canapé',
        'Siège > Chaise > Chaise',
        'Siège > Fauteuil > Fauteuil',
        'Siège > Fauteuil > Fauteuil de veille'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->toRemove as $typeToRemove) {
            $productType = ProductType::where('mapping_key', $typeToRemove)->first();

            if (!$productType) {
                echo "Type \"$typeToRemove\" n'existe pas ou déja supprimé\n";
                continue;
            }

            if ($productType->children()->count() > 0) {
                echo "Impossible de supprimer \"$typeToRemove\" car il a des enfants\n";
                continue;
            }
            $productType->delete();
            echo "\"$typeToRemove\" a été supprimés\n";
        }

        foreach ($this->toAdd as $typeToAdd) {
            $types_arr = explode(' > ', $typeToAdd);
            if (sizeof($types_arr) === 1) {
                ProductType::firstOrCreate(
                    ['mapping_key' => $typeToAdd],
                    ['name' => $typeToAdd]
                );
                echo "\"$typeToAdd\" crée\n";
            } else {
                $parent_type_keys = collect($types_arr);
                $name = $parent_type_keys->pop();
                $parent_type_key = $parent_type_keys->implode(' > ');
                $parent = ProductType::where('mapping_key', $parent_type_key)->first();

                if ($parent) {
                    ProductType::firstOrCreate(
                        ['mapping_key' => $typeToAdd],
                        [
                            'name' => $name,
                            'parent_id' => $parent->id
                        ]
                    );
                    echo "\"$typeToAdd\" crée\n";
                } else {
                    echo "Ce parent n'existe pas, merci de le crée: $parent_type_key\n";
                }
            }
        }
    }
}
