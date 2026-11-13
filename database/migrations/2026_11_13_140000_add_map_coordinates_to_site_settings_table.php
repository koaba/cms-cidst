<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Point GPS de l'etablissement : precision de 7 decimales (environ 1 cm), les deux ou aucune.
        Schema::table('site_settings', function (Blueprint $table) {
            $table->decimal('map_latitude', 10, 7)->nullable();
            $table->decimal('map_longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['map_latitude', 'map_longitude']);
        });
    }
};
