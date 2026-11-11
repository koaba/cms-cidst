<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Hauteur d'affichage du logo dans le menu public, en pixels (48 = l'ancien h-12).
            $table->unsignedSmallInteger('logo_height')->default(48)->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('logo_height');
        });
    }
};
