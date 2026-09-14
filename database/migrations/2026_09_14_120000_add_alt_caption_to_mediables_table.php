<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediables', function (Blueprint $table) {
            $table->string('alt')->nullable()->after('order');
            $table->string('caption')->nullable()->after('alt');
        });
    }

    public function down(): void
    {
        Schema::table('mediables', function (Blueprint $table) {
            $table->dropColumn(['alt', 'caption']);
        });
    }
};