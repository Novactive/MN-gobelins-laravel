<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPeriodsSetDefaultsNullable extends Migration
{
    public function up()
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->integer('start_year')->nullable()->default(null)->change();
            $table->integer('end_year')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('periods', function (Blueprint $table) {
            // Revert to previous state if needed (example without default)
            $table->integer('start_year')->nullable(false)->default(0)->change();
            $table->integer('end_year')->nullable(false)->default(0)->change();
        });
    }
}
