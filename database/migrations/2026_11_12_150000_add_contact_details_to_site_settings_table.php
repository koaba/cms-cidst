<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['contact_address', 'contact_phone', 'contact_phone_2', 'contact_whatsapp', 'contact_hours'];

    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_address')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_phone_2', 30)->nullable();
            $table->string('contact_whatsapp', 30)->nullable();
            $table->string('contact_hours')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
