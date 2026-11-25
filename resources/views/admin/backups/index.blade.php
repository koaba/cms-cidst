<x-admin.layout title="Sauvegardes">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold">Sauvegardes</h1>
        <form method="POST" action="{{ route('admin.backups.store') }}">
            @csrf
            <button type="submit" class="btn btn-primary" @disabled($busy)>Sauvegarder maintenant</button>
        </form>
    </div>

    @if (session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-error mb-4">{{ session('error') }}</div>@endif

    <p class="text-sm text-base-content/70 mb-4">
        Chaque sauvegarde contient la base de donn&eacute;es et les fichiers du site (images, PDF, vid&eacute;os).
        Les {{ $keep }} derni&egrave;res sont conserv&eacute;es sur le serveur
        @if ($free !== null) &middot; espace libre : {{ Illuminate\Support\Number::fileSize($free) }}@endif.
        Pensez &agrave; en t&eacute;l&eacute;charger une copie hors du serveur.
    </p>

    @if ($busy)
        <div class="alert alert-info mb-4" x-data x-init="setTimeout(() => location.reload(), 5000)">
            Sauvegarde en cours&hellip; cette page se met &agrave; jour toute seule.
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($backups as $backup)
            <div class="border border-base-300 rounded-lg p-4 flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $backup->created_at->format('d/m/Y H:i') }}</span>
                        <span class="badge {{ $backup->status->badge() }}">{{ $backup->status->label() }}</span>
                    </div>
                    <p class="text-sm text-base-content/60">
                        @if ($backup->requester) Demand&eacute;e par {{ $backup->requester->name }} @endif
                        @if ($backup->status === App\Enums\BackupStatus::Done)
                            &middot; base {{ Illuminate\Support\Number::fileSize($backup->db_size ?? 0) }}
                            &middot; fichiers {{ Illuminate\Support\Number::fileSize($backup->files_size ?? 0) }}
                        @endif
                    </p>
                    @if ($backup->error)<p class="text-sm text-cidst-danger break-words">{{ $backup->error }}</p>@endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($backup->status === App\Enums\BackupStatus::Done)
                        <a href="{{ route('admin.backups.download', [$backup, 'database']) }}" class="btn btn-sm btn-outline">Base</a>
                        <a href="{{ route('admin.backups.download', [$backup, 'files']) }}" class="btn btn-sm btn-outline">Fichiers</a>
                    @endif
                    @unless ($backup->status->isActive())
                        <x-delete-form :action="route('admin.backups.destroy', $backup)" message="Supprimer cette sauvegarde ?">
                            <button type="submit" class="text-cidst-danger">Supprimer</button>
                        </x-delete-form>
                    @endunless
                </div>
            </div>
        @empty
            <p class="p-4 text-center text-base-content/60">Aucune sauvegarde pour le moment.</p>
        @endforelse
    </div>
</x-admin.layout>
