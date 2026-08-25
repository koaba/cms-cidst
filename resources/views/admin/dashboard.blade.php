<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Tableau de bord</h1>

    {{-- Cartes de statistiques : composant natif daisyUI, zéro CSS custom --}}
    <div class="stats shadow w-full mb-8 flex-wrap">
        <div class="stat">
            <div class="stat-title">Articles publiés</div>
            <div class="stat-value text-primary">{{ $stats['articles'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Pages</div>
            <div class="stat-value text-primary">{{ $stats['pages'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Sliders</div>
            <div class="stat-value text-primary">{{ $stats['sliders'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Utilisateurs</div>
            <div class="stat-value text-primary">{{ $stats['users'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-title">Vues (30 derniers jours)</div>
            <div class="stat-value text-primary">{{ $stats['views_30d'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Graphique d'activité --}}
        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">Articles publiés — 6 derniers mois</h2>
                <canvas id="activityChart" height="90"></canvas>
            </div>
        </div>

        {{-- Flux d'activité récente --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">Activité récente</h2>
                <ul class="mt-2 space-y-3">
                    @foreach ($recentArticles as $article)
                        <li class="text-sm">
                            <span class="font-medium">{{ $article->title }}</span>
                            <span class="block text-xs opacity-60">
                                modifié le {{ $article->updated_at->format('d/m/Y H:i') }}
                                @if ($article->user)
                                    par {{ $article->user->name }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if ($recentUsers->isNotEmpty())
                    <div class="divider"></div>
                    <h3 class="font-medium text-sm mb-2">Derniers utilisateurs</h3>
                    <ul class="space-y-2">
                        @foreach ($recentUsers as $user)
                            <li class="text-sm">{{ $user->name }}
                                <span class="text-xs opacity-60">— {{ $user->created_at->format('d/m/Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Graphique des vues --}}
        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <h2 class="card-title">Vues du blog — 30 derniers jours</h2>
                <canvas id="viewsChart" height="90"></canvas>
            </div>
        </div>

        {{-- Top 5 articles les plus vus --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title">Top 5 articles (30j)</h2>
                @if ($topArticles->isEmpty() || $topArticles->sum('page_views_count') === 0)
                    <p class="text-sm opacity-60 mt-2">Aucune vue enregistrée pour l'instant.</p>
                @else
                    <ul class="mt-2 space-y-3">
                        @foreach ($topArticles as $article)
                            <li class="text-sm flex justify-between items-center gap-2">
                                <span class="font-medium truncate">{{ $article->title }}</span>
                                <span class="badge badge-primary badge-outline shrink-0">{{ $article->page_views_count }} vues</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.dashboardActivity = @json($activiteMensuelle);
            window.dashboardViews = @json($activiteVues);
        </script>
    @endpush
</x-admin.layout>