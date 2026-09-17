<?php

namespace App\Console\Commands;

use App\Models\PageBlock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditPhantomColumnBlocks extends Command
{
    protected $signature = 'blocks:audit-phantom-columns
                            {ids* : Les IDs des blocs colonnes à auditer}
                            {--fix : Applique la correction au lieu de juste auditer}';

    protected $description = 'Audite (et corrige si --fix) les anciens blocs colonnes au format pré-migration';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $apply = $this->option('fix');

        foreach (PageBlock::whereIn('id', $ids)->get() as $block) {
            $this->line("── Bloc #{$block->id} (page {$block->page_id}) ──");

            $hasRealChildren = PageBlock::where('parent_id', $block->id)->exists();
            $data = $block->data ?? [];
            $legacyColumns = $data['columns'] ?? null;

            $mediaLinked = DB::table('mediables')
                ->where('mediable_type', PageBlock::class)
                ->where('mediable_id', $block->id)
                ->exists();

            $this->line('Enfants PageBlock réels : ' . ($hasRealChildren ? 'OUI' : 'non'));
            $this->line('data.columns (legacy) : ' . ($legacyColumns ? json_encode($legacyColumns) : 'absent/vide'));
            $this->line('Média attaché directement : ' . ($mediaLinked ? 'OUI — ne pas supprimer sans traiter' : 'non'));

            $isEmpty = !$hasRealChildren && empty($legacyColumns) && !$mediaLinked;

            if ($isEmpty) {
                $this->warn('→ Candidat sûr à la suppression (vide, sans enfant, sans média).');
                if ($apply) {
                    $backupDir = storage_path('app/backups');
                    if (!is_dir($backupDir)) {
                        mkdir($backupDir, 0755, true);
                    }

                    file_put_contents(
                        "{$backupDir}/phantom-block-{$block->id}.json",
                        $block->toJson()
                    );
                    $block->delete();
                    $this->info("Bloc #{$block->id} sauvegardé puis supprimé.");
                }
            } else {
                $this->error('→ Contient du contenu réel : NE PAS supprimer automatiquement. Migration manuelle requise.');
            }
        }

        return self::SUCCESS;
    }
}