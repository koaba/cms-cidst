<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 100);
            $table->string('mime', 50);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_attachments');
    }
};
