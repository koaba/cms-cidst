<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            // Hash IP + navigateur + date du jour + APP_KEY : change chaque jour, jamais de donnee brute.
            $table->string('visitor_hash', 64);
            $table->string('referrer_host')->nullable();
            $table->string('device', 10);
            $table->timestamp('visited_at');

            $table->index('visited_at');
            $table->index(['visited_at', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
