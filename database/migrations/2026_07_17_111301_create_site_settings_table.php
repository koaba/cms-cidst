<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('site_settings', function (Blueprint $table) {
        $table->id();
        $table->string('hero_eyebrow')->default('Bienvenue');
        $table->string('hero_title')->default('Votre titre d\'accroche');
        $table->text('hero_subtitle')->nullable();
        $table->string('cta_primary_label')->nullable();
        $table->string('cta_primary_target')->nullable();
        $table->string('cta_secondary_label')->nullable();
        $table->string('cta_secondary_target')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
