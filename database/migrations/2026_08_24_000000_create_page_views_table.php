<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();

            // Polymorphique dès le départ : permet de tracker les vues d'Article
            // aujourd'hui, et de tout autre modèle (Page, PdfDocument...) demain,
            // sans nouvelle migration ni changement de structure.
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');

            // IP hashée (jamais en clair) pour permettre le dédoublonnage sans
            // stocker de donnée personnelle identifiable â€” conforme RGPD.
            $table->string('ip_hash', 64);
            $table->string('user_agent')->nullable();

            $table->timestamp('viewed_at');

            $table->index(['viewable_type', 'viewable_id']);
            $table->index('viewed_at');
            // Index dédié au dédoublonnage (recherche "cette IP a-t-elle déjà vu
            // cet article récemment ?"), évite un scan complet de la table.
            $table->index(['viewable_type', 'viewable_id', 'ip_hash', 'viewed_at'], 'page_views_dedup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};