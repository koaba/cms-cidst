<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('show_footer')->default(true);
            $table->string('footer_description')->nullable();
            $table->boolean('footer_show_contacts')->default(true);
            $table->string('footer_copyright')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['show_footer', 'footer_description', 'footer_show_contacts', 'footer_copyright']);
        });
    }
};
