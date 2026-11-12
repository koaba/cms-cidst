<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defauts alignes sur ThemeStyleBuilder::DEFAULT_PRIMARY / DEFAULT_SECONDARY.
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#C41E2A')->change();
            $table->string('secondary_color', 7)->default('#17181A')->change();
        });

        $this->alignExistingRows();
    }

    /**
     * Les reglages jamais personnalises (les deux couleurs encore aux anciens defauts) passent
     * aux nouvelles valeurs ; une seule couleur modifiee suffit a laisser la ligne intacte.
     * Mise a jour par requete : aucun observer, le cache de SiteSetting::current() se vide
     * avec cache:clear apres le deploiement.
     */
    public function alignExistingRows(): void
    {
        DB::table('site_settings')
            ->where('primary_color', '#E80000')
            ->where('secondary_color', '#1A1A1A')
            ->update([
                'primary_color' => '#C41E2A',
                'secondary_color' => '#17181A',
            ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#E80000')->change();
            $table->string('secondary_color', 7)->default('#1A1A1A')->change();
        });
    }
};
