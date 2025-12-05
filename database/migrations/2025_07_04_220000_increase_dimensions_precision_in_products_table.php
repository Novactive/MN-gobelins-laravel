<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseDimensionsPrecisionInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('height_or_thickness', 8, 3)->nullable()->change();
            $table->decimal('length_or_diameter', 8, 3)->nullable()->change();
            $table->decimal('depth_or_width', 8, 3)->nullable()->change();
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
            $table->decimal('height_or_thickness', 5, 3)->nullable()->change();
            $table->decimal('length_or_diameter', 5, 3)->nullable()->change();
            $table->decimal('depth_or_width', 5, 3)->nullable()->change();
        });
    }
} 