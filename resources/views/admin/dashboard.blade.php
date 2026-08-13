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
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

    @push('scripts')
        <script>
            window.dashboardActivity = @json($activiteMensuelle);
        </script>
    @endpush
</x-admin.layout>