<x-admin.layout title="Sauvegardes">
    <h1 class="text-2xl font-bold mb-4">Sauvegardes</h1>

    @if (session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-error mb-4">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-error mb-4">
            <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="alert alert-warning mb-6 text-sm">
        Les sauvegardes restent sur le serveur : t&eacute;l&eacute;chargez-en r&eacute;guli&egrave;rement une copie (boutons Base et Fichiers) et conservez-la ailleurs.
    </div>

    <section class="card bg-base-100 shadow mb-6" aria-labelledby="backup-now">
        <div class="card-body">
            <h2 id="backup-now" class="card-title text-lg">Sauvegarder maintenant</h2>
            <p class="text-sm text-base-content/70">
                Prot&egrave;ge la base de donn&eacute;es (articles, pages, r&eacute;glages, utilisateurs) et tous les fichiers :
                {{ $inventory['media'] }} m&eacute;dias, {{ $inventory['pdf'] }} documents PDF, miniatures, vid&eacute;os et pi&egrave;ces jointes
                de {{ $inventory['messages'] }} messages de contact.
                @if ($free !== null) Espace libre sur le serveur : {{ Illuminate\Support\Number::fileSize($free) }}.@endif
            </p>
            @if ($busy)
                <div class="alert alert-info text-sm" x-data x-init="setTimeout(() => location.reload(), 5000)">
                    Sauvegarde en cours&hellip; cette page se met &agrave; jour toute seule.
                </div>
            @endif
            <div class="flex flex-wrap gap-3">
                @foreach (App\Enums\BackupScope::cases() as $scope)
                    <form method="POST" action="{{ route('admin.backups.store') }}">
                        @csrf
                        <input type="hidden" name="scope" value="{{ $scope->value }}">
                        <button type="submit" class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline' }}" @disabled($busy)>{{ $scope->button() }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>

    <section class="card bg-base-100 shadow mb-6" aria-labelledby="backup-auto">
        <div class="card-body">
            <h2 id="backup-auto" class="card-title text-lg">Sauvegarde automatique</h2>
            <form method="POST" action="{{ route('admin.backups.settings') }}" x-data="{ freq: @js($settings['frequency']) }" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <input type="hidden" name="auto" value="0">
                <label class="flex items-center gap-3 cursor-pointer sm:col-span-2">
                    <input type="checkbox" name="auto" value="1" class="toggle toggle-primary" @checked($settings['auto'])>
                    <span>Activer la sauvegarde automatique</span>
                </label>

                <input type="hidden" name="mirror" value="0">
                <label class="flex items-center gap-3 cursor-pointer sm:col-span-2">
                    <input type="checkbox" name="mirror" value="1" class="toggle toggle-primary" @checked($settings['mirror'])>
                    <span>Copie miroir des fichiers, mise &agrave; jour toutes les 5 minutes</span>
                </label>

                <label class="form-control">
                    <span class="label-text mb-1">Fr&eacute;quence</span>
                    <select name="frequency" x-model="freq" class="select select-bordered w-full">
                        <option value="daily">Tous les jours</option>
                        <option value="weekly">Toutes les semaines</option>
                    </select>
                </label>

                <label class="form-control" x-show="freq === 'weekly'">
                    <span class="label-text mb-1">Jour</span>
                    <select name="weekday" class="select select-bordered w-full">
                        @foreach ($weekdays as $number => $name)
                            <option value="{{ $number }}" @selected($settings['weekday'] === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="form-control">
                    <span class="label-text mb-1">Heure (heure du site)</span>
                    <select name="hour" class="select select-bordered w-full">
                        @for ($h = 0; $h < 24; $h++)
                            <option value="{{ $h }}" @selected($settings['hour'] === $h)>{{ sprintf('%02d', $h) }} h</option>
                        @endfor
                    </select>
                </label>

                <label class="form-control">
                    <span class="label-text mb-1">Contenu</span>
                    <select name="scope" class="select select-bordered w-full">
                        @foreach (App\Enums\BackupScope::cases() as $scope)
                            <option value="{{ $scope->value }}" @selected($settings['scope'] === $scope->value)>{{ $scope->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="form-control">
                    <span class="label-text mb-1">Sauvegardes conserv&eacute;es (par contenu)</span>
                    <input type="number" name="keep" min="1" max="30" value="{{ $settings['keep'] }}" class="input input-bordered w-full">
                </label>

                <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    @if ($settings['auto'] && $next)
                        <span class="text-sm text-base-content/70">Prochaine sauvegarde : {{ $next->format('d/m/Y') }} &agrave; {{ $next->format('H') }} h.</span>
                    @else
                        <span class="text-sm text-base-content/70">Sauvegarde automatique d&eacute;sactiv&eacute;e.</span>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <section class="card bg-base-100 shadow mb-6" aria-labelledby="backup-mirror">
        <div class="card-body">
            <h2 id="backup-mirror" class="card-title text-lg">Copie miroir des fichiers</h2>
            <p class="text-sm text-base-content/70">
                @if ($mirror['synced_at'])
                    Derni&egrave;re copie : {{ $mirror['synced_at']->format('d/m/Y') }} &agrave; {{ $mirror['synced_at']->format('H:i') }},
                    {{ $mirror['files'] }} fichiers ({{ Illuminate\Support\Number::fileSize($mirror['bytes']) }}).
                @else
                    Aucune copie miroir pour le moment.
                @endif
                @if ($mirror['restored_at'])
                    Derni&egrave;re restauration : {{ $mirror['restored_at']->format('d/m/Y') }} &agrave; {{ $mirror['restored_at']->format('H:i') }}.
                @endif
            </p>
            <p class="text-sm text-base-content/70">
                Le miroir double l&rsquo;espace utilis&eacute; par les fichiers du site et reste sur le m&ecirc;me serveur : une panne du serveur l&rsquo;emporte.
                Il ne supprime jamais rien. Pour une vraie s&eacute;curit&eacute;, t&eacute;l&eacute;chargez une sauvegarde compl&egrave;te.
            </p>
            @if ($mirror['error'])<div class="alert alert-error text-sm break-words">{{ $mirror['error'] }}</div>@endif
            @if ($mirror['request'])
                <div class="alert alert-info text-sm">
                    {{ $mirror['request'] === 'restore' ? 'Restauration' : 'Copie' }} demand&eacute;e : elle d&eacute;marre dans les 5 minutes.
                </div>
            @endif
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.backups.mirror') }}">
                    @csrf
                    <input type="hidden" name="action" value="sync">
                    <button type="submit" class="btn btn-outline" @disabled($mirror['request'])>Copier maintenant</button>
                </form>
                <form method="POST" action="{{ route('admin.backups.mirror') }}" x-data
                      @submit="if (! confirm('Restaurer les fichiers du miroir qui manquent sur le site ? Les fichiers existants ne sont pas modifi&eacute;s. Les fichiers supprim&eacute;s volontairement r&eacute;appara&icirc;tront comme fichiers orphelins, invisibles sur le site.')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="action" value="restore">
                    <button type="submit" class="btn btn-outline" @disabled($mirror['request'] || ! $mirror['synced_at'])>Restaurer</button>
                </form>
            </div>
            <div class="flex flex-wrap items-center gap-3 border-t border-base-300 pt-4">
                <span class="font-semibold">Tout t&eacute;l&eacute;charger</span>
                @if ($latestFull)
                    <span class="text-sm text-base-content/70">sauvegarde compl&egrave;te du {{ $latestFull->created_at->format('d/m/Y H:i') }} :</span>
                    <a href="{{ route('admin.backups.download', [$latestFull, 'database']) }}" class="btn btn-sm btn-outline">Base</a>
                    <a href="{{ route('admin.backups.download', [$latestFull, 'files']) }}" class="btn btn-sm btn-outline">Fichiers</a>
                @else
                    <span class="text-sm text-base-content/70">Aucune sauvegarde compl&egrave;te termin&eacute;e : lancez &laquo; Tout sauvegarder &raquo;.</span>
                @endif
            </div>
        </div>
    </section>

    <h2 class="text-lg font-semibold mb-3">Historique</h2>
    <div class="flex flex-col gap-3">
        @forelse ($backups as $backup)
            <div class="border border-base-300 rounded-lg p-4 flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $backup->created_at->format('d/m/Y H:i') }}</span>
                        <span class="badge {{ $backup->status->badge() }}">{{ $backup->status->label() }}</span>
                        <span class="badge badge-ghost">{{ $backup->scope->label() }}</span>
                    </div>
                    <p class="text-sm text-base-content/60">
                        @if ($backup->automatic) Automatique @elseif ($backup->requester) Demand&eacute;e par {{ $backup->requester->name }} @endif
                        @if ($backup->db_size !== null) &middot; base {{ Illuminate\Support\Number::fileSize($backup->db_size) }} @endif
                        @if ($backup->files_size !== null) &middot; fichiers {{ Illuminate\Support\Number::fileSize($backup->files_size) }} @endif
                    </p>
                    @if ($backup->error)<p class="text-sm text-cidst-danger break-words">{{ $backup->error }}</p>@endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($backup->hasFile('database'))
                        <a href="{{ route('admin.backups.download', [$backup, 'database']) }}" class="btn btn-sm btn-outline">Base</a>
                    @endif
                    @if ($backup->hasFile('files'))
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
