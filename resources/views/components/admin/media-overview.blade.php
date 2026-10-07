@props(['overview'])

@php
    $counts = $overview['counts'];
    $alerts = $overview['alerts'];
    $total = max(array_sum($counts), 1);

    $kinds = [
        'image' => ['label' => 'Images', 'bar' => 'bg-primary'],
        'pdf' => ['label' => 'PDF', 'bar' => 'bg-neutral'],
        'video' => ['label' => 'Vidéos', 'bar' => 'bg-base-300'],
    ];

    $badges = ['image' => 'IMG', 'pdf' => 'PDF', 'video' => 'VID', 'other' => 'FIC'];

    $heavyLabel = \App\Services\MediaOverviewService::formatBytes(\App\Services\MediaOverviewService::HEAVY_IMAGE_BYTES);

    $alertLabels = [
        'orphans' => 'Médias non utilisés',
        'heavy_images' => 'Images de plus de '.$heavyLabel,
        'missing_alt' => 'Images sans texte alternatif',
    ];
@endphp

<section class="mb-8" aria-labelledby="media-overview-title">
    <div class="flex items-center justify-between mb-3">
        <h2 id="media-overview-title" class="text-lg font-semibold">Médiathèque</h2>
        <a href="{{ route('admin.media.index') }}" class="link link-hover text-sm">Voir tout</a>
    </div>

    {{-- Même bandeau que les statistiques du dessus --}}
    <div class="stats shadow w-full flex-wrap">
        @foreach ($kinds as $key => $kind)
            <div class="stat">
                <div class="stat-title">{{ $kind['label'] }}</div>
                <div class="stat-value text-primary">{{ $counts[$key] }}</div>
            </div>
        @endforeach
        <div class="stat">
            <div class="stat-title">Poids total</div>
            <div class="stat-value text-primary">{{ $overview['total_size'] }}</div>
        </div>
    </div>

    {{-- Répartition : une barre segmentée en CSS pur, sans bibliothèque --}}
    <div class="mt-3 flex h-2 w-full overflow-hidden rounded-full bg-base-200" role="img"
         aria-label="Répartition : {{ $counts['image'] }} images, {{ $counts['pdf'] }} PDF, {{ $counts['video'] }} vidéos">
        @foreach ($kinds as $key => $kind)
            @if ($counts[$key] > 0)
                <div class="{{ $kind['bar'] }}" style="width: {{ round($counts[$key] / $total * 100, 1) }}%"></div>
            @endif
        @endforeach
    </div>
    <ul class="mt-2 flex flex-wrap gap-4 text-xs opacity-70">
        @foreach ($kinds as $key => $kind)
            <li class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full {{ $kind['bar'] }}"></span>{{ $kind['label'] }}
            </li>
        @endforeach
    </ul>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Derniers ajouts --}}
        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <h3 class="card-title text-base">Derniers ajouts</h3>

                @if (empty($overview['recent']))
                    <p class="text-sm opacity-60 mt-2">Aucun média pour l'instant.</p>
                @else
                    <ul class="mt-2 divide-y divide-base-200">
                        @foreach ($overview['recent'] as $item)
                            <li class="flex items-center gap-3 py-2">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded bg-base-200 text-xs font-semibold opacity-80">
                                    @if ($item['thumb'])
                                        <img src="{{ $item['thumb'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        {{ $badges[$item['kind']] }}
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $item['name'] }}</p>
                                    <p class="text-xs opacity-60">
                                        {{ $item['size'] }}
                                        @if ($item['created_at'])
                                            · {{ $item['created_at']->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>

                                @if ($item['used'] > 0)
                                    <span class="badge badge-ghost badge-sm shrink-0">Utilisé {{ $item['used'] }}×</span>
                                @else
                                    <span class="badge badge-warning badge-outline badge-sm shrink-0">Non utilisé</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- À surveiller --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h3 class="card-title text-base">À surveiller</h3>

                @if (array_sum($alerts) === 0)
                    <p class="text-sm opacity-60 mt-2">Tout est en ordre.</p>
                @else
                    <ul class="mt-2 space-y-3">
                        @foreach ($alertLabels as $key => $label)
                            <li class="flex items-center justify-between gap-2 text-sm">
                                <span>{{ $label }}</span>
                                @if ($alerts[$key] > 0)
                                    <span class="badge badge-warning">{{ $alerts[$key] }}</span>
                                @else
                                    <span class="badge badge-ghost">0</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</section>