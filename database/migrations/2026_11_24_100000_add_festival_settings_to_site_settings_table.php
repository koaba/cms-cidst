<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Interrupteur general des fetes (le .env FESTIVALS_ENABLED reste prioritaire).
            $table->boolean('festivals_enabled')->default(true);
            // Date de simulation, visible du seul Super Admin connecte.
            $table->date('festivals_simulated_on')->nullable();
            // Options par fete (activation, emplacement, icones, animation) : voir FestivalSettings.
            $table->json('festivals_options')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['festivals_enabled', 'festivals_simulated_on', 'festivals_options']);
        });
    }
};
