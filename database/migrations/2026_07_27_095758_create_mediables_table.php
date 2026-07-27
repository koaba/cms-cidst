<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedBigInteger('mediable_id');
            $table->string('mediable_type');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['mediable_id', 'mediable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};