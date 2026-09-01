<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_watermark_defaults_to_site_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('image_watermark_default_enabled')->default(false)->after('video_watermark_default_enabled');
            $table->boolean('pdf_watermark_default_enabled')->default(false)->after('image_watermark_default_enabled');
            $table->boolean('diaporama_watermark_default_enabled')->default(false)->after('pdf_watermark_default_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['image_watermark_default_enabled', 'pdf_watermark_default_enabled', 'diaporama_watermark_default_enabled']);
        });
    }
};
