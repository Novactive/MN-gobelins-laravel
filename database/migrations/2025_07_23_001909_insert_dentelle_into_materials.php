<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertDentelleIntoMaterials extends Migration
{
    public function up()
    {
        DB::table('materials')->insert([
            'name' => 'Dentelle',
            'mapping_key' => 'Dentelle',
            'is_textile_technique' => true,
        ]);
    }

    public function down()
    {
        // Supprimer l'entrée si on rollback
        DB::table('materials')
            ->where('name', 'Dentelle')
            ->where('mapping_key', 'Textile > Dentelle')
            ->delete();
    }
}

