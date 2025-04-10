<?php

use Illuminate\Database\Seeder;
use App\Models\Material;

class MaterialUpdateSeeder extends Seeder
{

    private $toAdd = [
        "Peinture",
        "Peinture > Peinture à l'huile",
        "Papier",
        "Papier > Carton",
        "Verre > Peint",
        "Verre > Miroir",
        "Cannage"
    ];

    private $toRemove = [
        'Métal > Graphite'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->toRemove as $materialToRemove) {
            $material = Material::where('mapping_key', $materialToRemove)->first();

            if (!$material) {
                echo "Matériel \"$materialToRemove\" n'existe pas ou déja supprimé\n";
                continue;
            }

            if ($material->children()->count() > 0) {
                echo "Impossible de supprimer \"$materialToRemove\" car il a des enfants\n";
                continue;
            }
            $material->delete();
            echo "\"$materialToRemove\" a été supprimés\n";
        }

        foreach ($this->toAdd as $materialToAdd) {
            $types_arr = explode(' > ', $materialToAdd);
            if (sizeof($types_arr) === 1) {
                Material::firstOrCreate(
                    ['mapping_key' => $materialToAdd],
                    [
                        'name' => $materialToAdd,
                        'is_textile_technique' => false
                    ],
                );
                echo "\"$materialToAdd\" crée\n";
            } else {
                $parent_material_keys = collect($types_arr);
                $name = $parent_material_keys->pop();
                $parent_material_key = $parent_material_keys->implode(' > ');
                $parent = Material::where('mapping_key', $parent_material_key)->first();

                if ($parent) {
                    Material::firstOrCreate(
                        ['mapping_key' => $materialToAdd],
                        [
                            'name' => $name,
                            'parent_id' => $parent->id,
                            'is_textile_technique' => false
                        ]
                    );
                    echo "\"$materialToAdd\" crée\n";
                } else {
                    echo "Ce parent n'existe pas, merci de le crée: $parent_material_key\n";
                }
            }
        }
    }
}
