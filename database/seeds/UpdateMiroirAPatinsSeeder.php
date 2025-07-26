<?php

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class UpdateMiroirAPatinsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productType = ProductType::where('mapping_key', 'Meuble > Miroir > Miroir à patin')->first();

        if (!$productType) {
            echo "Product type 'Meuble > Miroir > Miroir à patin' n'existe pas\n";
            return;
        }

        $productType->update(['name' => 'Miroir à patins']);
        echo "Product type 'Meuble > Miroir > Miroir à patin' mis à jour vers 'Miroir à patins'\n";
    }
} 