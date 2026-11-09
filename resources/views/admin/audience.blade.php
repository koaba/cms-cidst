<x-admin.layout title="Audience">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold">Audience</h1>
        <nav class="join" aria-label="P&eacute;riode">
            @foreach (['week' => '7 jours', 'month' => '30 jours', 'year' => '12 mois'] as $key => $label)
                <a href="{{ route('admin.audience', ['period' => $key]) }}"
                   class="join-item btn btn-sm {{ $period === $key ? 'btn-neutral' : 'btn-ghost bg-base-100' }}"
                   @if ($period === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <div class="stats shadow w-full mb-8 flex-wrap">
        <div class="stat" data-audience="visitors" data-count="{{ $summary['visitors'] }}">
            <div class="stat-title">Visiteurs (cumul des jours)</div>
            <div class="stat-value text-primary">{{ $summary['visitors'] }}</div>
        </div>
        <div class="stat" data-audience="visits" data-count="{{ $summary['visits'] }}">
            <div class="stat-title">Pages vues</div>
            <div class="stat-value text-primary">{{ $summary['visits'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">Pages les plus vues</h2>
                <ul class="space-y-1 text-sm">
                    @forelse ($summary['top_pages'] as $path => $count)
                        <li class="flex justify-between gap-3"><span class="truncate">{{ $path }}</span><span class="font-medium">{{ $count }}</span></li>
                    @empty
                        <li class="opacity-60">Aucune visite sur la p&eacute;riode.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">Provenance</h2>
                <ul class="space-y-1 text-sm">
                    @forelse ($summary['referrers'] as $host => $count)
                        <li class="flex justify-between gap-3"><span class="truncate">{{ $host }}</span><span class="font-medium">{{ $count }}</span></li>
                    @empty
                        <li class="opacity-60">Aucune provenance externe.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">Appareils</h2>
                <ul class="space-y-1 text-sm">
                    @forelse ($summary['devices'] as $device => $count)
                        <li class="flex justify-between gap-3"><span>{{ $device === 'mobile' ? 'Mobile' : 'Ordinateur' }}</span><span class="font-medium">{{ $count }}</span></li>
                    @empty
                        <li class="opacity-60">Aucune visite sur la p&eacute;riode.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <section id="historique" class="card bg-base-100 shadow mt-8" aria-labelledby="historique-title">
        <div class="card-body">
            <h2 id="historique-title" class="card-title text-base">Historique mensuel</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Mois</th><th class="text-right">Pages vues</th><th class="text-right">Visiteurs (cumul des jours)</th><th class="text-right">Mobile</th></tr></thead>
                    <tbody>
                        @forelse ($monthly as $month => $row)
                            <tr data-month="{{ $month }}"><td>{{ $month }}</td><td class="text-right">{{ $row['visits'] }}</td><td class="text-right">{{ $row['visitors'] }}</td><td class="text-right">{{ $row['mobile'] }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="opacity-60">Aucune donn&eacute;e pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <p class="text-xs opacity-60 mt-6">Visites anonymes : aucune adresse IP n'est conserv&eacute;e, et un visiteur est compt&eacute; une fois par jour.</p>
</x-admin.layout>
