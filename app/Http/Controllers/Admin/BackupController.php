<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BackupScope;
use App\Enums\BackupStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBackupSettingsRequest;
use App\Models\Backup;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\PdfDocument;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupSettings;
use App\Services\Backup\FileMirror;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /** Fichier telecharge selon le segment d'URL (liste blanche : jamais de chemin venu de l'utilisateur). */
    private const FILES = [
        'database' => BackupService::DATABASE_FILE,
        'files' => BackupService::STORAGE_FILE,
    ];

    public function index(BackupSettings $settings, FileMirror $mirror): View
    {
        $free = @disk_free_space(Backup::root() !== '' && is_dir(Backup::root()) ? Backup::root() : storage_path());

        $backups = Backup::query()->with('requester:id,name')->latest('id')->limit(30)->get();

        return view('admin.backups.index', [
            'backups' => $backups,
            'latestFull' => $backups->first(fn (Backup $backup) => $backup->scope === BackupScope::All && $backup->status === BackupStatus::Done),
            'mirror' => $mirror->state(),
            'busy' => Backup::active()->exists(),
            'free' => $free === false ? null : (int) $free,
            'settings' => $settings->all(),
            'next' => $settings->nextDueAt(now()),
            'weekdays' => BackupSettings::WEEKDAYS,
            'inventory' => [
                'media' => Media::count(),
                'pdf' => PdfDocument::count(),
                'messages' => ContactMessage::count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['scope' => ['nullable', Rule::enum(BackupScope::class)]]);

        if (Backup::active()->exists()) {
            return back()->with('error', "Une sauvegarde est d\u{e9}j\u{e0} en attente ou en cours.");
        }

        Backup::create([
            'status' => BackupStatus::Pending,
            'scope' => BackupScope::tryFrom((string) $request->input('scope')) ?? BackupScope::All,
            'requested_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Sauvegarde demand\u{e9}e : elle d\u{e9}marre dans la minute.");
    }

    /** Demande de copie ou de restauration des fichiers : traitee par backup:sync-files, jamais dans la requete. */
    public function mirror(Request $request, FileMirror $mirror): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(FileMirror::ACTIONS)]]);

        if ($mirror->state()['request'] !== null) {
            return back()->with('error', "Une copie ou une restauration est d\u{e9}j\u{e0} en attente.");
        }

        $mirror->request((string) $data['action']);

        return back()->with('success', "Demande enregistr\u{e9}e : elle d\u{e9}marre dans les 5 minutes.");
    }

    public function updateSettings(UpdateBackupSettingsRequest $request, BackupSettings $settings): RedirectResponse
    {
        $settings->update($request->validated());

        return back()->with('success', "Options de sauvegarde enregistr\u{e9}es.");
    }

    public function download(Backup $backup, string $file): BinaryFileResponse
    {
        abort_unless($backup->hasFile($file), 404);

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
