<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reseaux sociaux du pied de page (lot 3h-1d-3) : YouTube et LinkedIn.
 * Facebook existe deja (facebook_url).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Noms en toutes lettres : Larastan deduit les colonnes du modele en lisant les migrations.
            $table->string('youtube_url')->nullable();
            $table->string('linkedin_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'linkedin_url']);
        });
    }
};
