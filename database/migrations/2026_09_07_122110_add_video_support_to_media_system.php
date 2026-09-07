<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->enum('type', ['image', 'document', 'video'])->default('image')->after('id');
            $table->enum('source_type', ['upload', 'external'])->default('upload')->after('type');
            $table->string('url')->nullable()->after('path');
            $table->boolean('apply_watermark')->default(false)->after('mime_type');
        });

        DB::table('media')
            ->where('mime_type', 'application/pdf')
            ->update(['type' => 'document']);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['type', 'source_type', 'url', 'apply_watermark']);
            $table->string('path')->nullable(false)->change();
        });
    }
};
