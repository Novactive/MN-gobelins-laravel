<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZetcomAuthorIdToAuthorsTable extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->unsignedBigInteger('zetcom_author_id')->nullable()->after('legacy_id');
            $table->index('zetcom_author_id');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropIndex(['zetcom_author_id']);
            $table->dropColumn('zetcom_author_id');
        });
    }
}


