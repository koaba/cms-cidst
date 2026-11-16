<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emplacement d'un element de menu : main (menu principal), footer (pied de page)
 * ou legal (mentions legales). Les elements existants restent dans le menu principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('location', 20)->default('main')->after('parent_id');
            $table->index(['location', 'is_active', 'order'], 'menus_location_is_active_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex('menus_location_is_active_order_index');
            $table->dropColumn('location');
        });
    }
};
