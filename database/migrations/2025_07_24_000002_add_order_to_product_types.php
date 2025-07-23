<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddOrderToProductTypes extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('product_types', 'order')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->integer('order')->nullable()->after('mapping_key');
            });
        }

        $productTypes = DB::table('product_types')->get()->all();

        // Utilisation de Collator pour un tri correct avec accents
        $collator = new \Collator('fr_FR');
        usort($productTypes, function($a, $b) use ($collator) {
            return $collator->compare($a->name, $b->name);
        });

        $order = 10;
        foreach ($productTypes as $pt) {
            DB::table('product_types')->where('id', $pt->id)->update(['order' => $order]);
            $order += 10;
        }
    }

    public function down()
    {
        if (Schema::hasColumn('product_types', 'order')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->dropColumn('order');
            });
        }
    }
} 