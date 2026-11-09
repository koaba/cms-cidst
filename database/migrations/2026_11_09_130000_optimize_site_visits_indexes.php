<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            // visited_at seul est un prefixe de (visited_at, visitor_hash) : inutile a entretenir a chaque visite.
            $table->dropIndex(['visited_at']);
            // Classement des pages les plus vues sur une periode.
            $table->index(['visited_at', 'path']);
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropIndex(['visited_at', 'path']);
            $table->index('visited_at');
        });
    }
};
