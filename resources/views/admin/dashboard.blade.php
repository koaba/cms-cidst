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