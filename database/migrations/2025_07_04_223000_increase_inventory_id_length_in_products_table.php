<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseInventoryIdLengthInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['inventory_id']);
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->string('inventory_id', 50)->change();
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->unique('inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['inventory_id']);
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->string('inventory_id', 20)->change();
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->unique('inventory_id');
        });
    }
} 