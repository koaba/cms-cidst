<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BackupStatus;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /** Fichier telecharge selon le segment d'URL (liste blanche : jamais de chemin venu de l'utilisateur). */
    private const FILES = [
        'database' => BackupService::DATABASE_FILE,
        'files' => BackupService::STORAGE_FILE,
    ];

    public function index(): View
    {
        $free = @disk_free_space(Backup::root() !== '' && is_dir(Backup::root()) ? Backup::root() : storage_path());

        return view('admin.backups.index', [
            'backups' => Backup::query()->with('requester:id,name')->latest('id')->limit(30)->get(),
            'busy' => Backup::active()->exists(),
            'free' => $free === false ? null : (int) $free,
            'keep' => (int) config('backup.keep'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (Backup::active()->exists()) {
            return back()->with('error', "Une sauvegarde est d\u{e9}j\u{e0} en attente ou en cours.");
        }

        Backup::create(['status' => BackupStatus::Pending, 'requested_by' => $request->user()?->id]);

        return back()->with('success', "Sauvegarde demand\u{e9}e : elle d\u{e9}marre dans la minute.");
    }

    public function download(Backup $backup, string $file): BinaryFileResponse
    {
        abort_unless($backup->status === BackupStatus::Done && isset(self::FILES[$file]), 404);

        $path = $backup->filePath(self::FILES[$file]);

        abort_unless(is_file($path), 404);

        return response()->download($path, 'cidst-'.$backup->directory.'-'.self::FILES[$file]);
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        abort_if($backup->status->isActive(), 409);

        $backup->delete();

        return back()->with('success', "Sauvegarde supprim\u{e9}e.");
    }
}
