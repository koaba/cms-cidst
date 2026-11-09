<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Totaux anonymes par jour, conserves sans limite pour les rapports.
        Schema::create('site_visit_daily', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('visits');
            $table->unsignedInteger('visitors');
            $table->unsignedInteger('mobile_visits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visit_daily');
    }
};
