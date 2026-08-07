<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diaporamas', function (Blueprint $table) {
            $table->id();
            $table->morphs('diaporamable'); // diaporamable_type, diaporamable_id + index
            $table->string('title')->nullable();
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaporamas');
    }
};