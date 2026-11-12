<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Dimensions reelles du fichier du logo : width et height de la balise img (evite le CLS).
            $table->unsignedSmallInteger('logo_width')->nullable()->after('logo_height');
            $table->unsignedSmallInteger('logo_natural_height')->nullable()->after('logo_width');
        });

        $this->backfill();
    }

    /**
     * Mesure les logos deja en place. Publique : le test l'appelle sans passer par up()
     * (un ALTER TABLE ferait un commit implicite sur MySQL).
     * Par le modele : l'enregistrement vide le cache des reglages du site.
     */
    public function backfill(): void
    {
        $disk = Storage::disk('public');

        SiteSetting::query()->whereNotNull('logo_path')->whereNull('logo_width')->get()
            ->each(function (SiteSetting $settings) use ($disk) {
                $size = rescue(fn () => getimagesize($disk->path((string) $settings->logo_path)), false, false);

                if ($size) {
                    $settings->update(['logo_width' => $size[0], 'logo_natural_height' => $size[1]]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_width', 'logo_natural_height']);
        });
    }
};
