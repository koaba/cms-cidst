<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Couleurs des sept roles (cle = role, valeur = #RRGGBB) : extensible sans migration.
            // Nul tant que rien n'est personnalise : les defauts de ThemeRoles s'appliquent.
            $table->json('theme_colors')->nullable()->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('theme_colors');
        });
    }
};
