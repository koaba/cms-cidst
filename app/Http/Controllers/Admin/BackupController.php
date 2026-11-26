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

    public function index(BackupSettings $settings): View
    {
        $free = @disk_free_space(Backup::root() !== '' && is_dir(Backup::root()) ? Backup::root() : storage_path());

        return view('admin.backups.index', [
            'backups' => Backup::query()->with('requester:id,name')->latest('id')->limit(30)->get(),
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
