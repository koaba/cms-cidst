<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('page_id')
                ->constrained('page_blocks')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('column_index')
                ->nullable()
                ->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'column_index']);
        });
    }
};