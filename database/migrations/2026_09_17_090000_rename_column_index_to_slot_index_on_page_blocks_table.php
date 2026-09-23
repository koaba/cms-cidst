<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Colonne déjà renommée lors d'une exécution précédente — on ne recrée que l'index manquant.
        if (! Schema::hasColumn('page_blocks', 'column_index') && Schema::hasColumn('page_blocks', 'slot_index')) {
            Schema::table('page_blocks', function (Blueprint $table) {
                $table->index(['parent_id', 'slot_index']);
            });
            return;
        }

        Schema::table('page_blocks', function (Blueprint $table) {
            $table->renameColumn('column_index', 'slot_index');
        });

        Schema::table('page_blocks', function (Blueprint $table) {
            $table->index(['parent_id', 'slot_index']);
        });
    }

    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'slot_index']);
        });

        Schema::table('page_blocks', function (Blueprint $table) {
            $table->renameColumn('slot_index', 'column_index');
        });

        Schema::table('page_blocks', function (Blueprint $table) {
            $table->index(['parent_id', 'column_index']);
        });
    }
};