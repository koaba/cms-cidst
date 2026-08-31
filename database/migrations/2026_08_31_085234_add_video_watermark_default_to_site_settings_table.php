<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_video_watermark_default_to_site_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('video_watermark_default_enabled')->default(false)->after('news_ticker_direction');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('video_watermark_default_enabled');
        });
    }
};