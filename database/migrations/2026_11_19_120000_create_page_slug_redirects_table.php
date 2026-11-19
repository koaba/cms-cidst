<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anciens slugs des pages renommees : redirection 301 vers l'adresse actuelle (SEO).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('old_slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_slug_redirects');
    }
};
