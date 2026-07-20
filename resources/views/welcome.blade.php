<x-layout title="CIDST — Centre d'Information et de Documentation Scientifique et Technique">

    @php($settings = \App\Models\SiteSetting::current())

    {{-- HERO --}}
    <section class="relative overflow-hidden -mx-4 sm:-mx-6 px-4 sm:px-6 -mt-10 pt-10">

        {{-- Motif de fond : nœuds reliés, écho discret du logo --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.04] pointer-events-none" preserveAspectRatio="none">
            <defs>
                <pattern id="nodes" width="90" height="90" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="3" fill="#17181A" />
                    <line x1="10" y1="10" x2="80" y2="10" stroke="#17181A" stroke-width="1" />
                    <line x1="10" y1="10" x2="10" y2="80" stroke="#17181A" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#nodes)" />
        </svg>

        <div class="relative py-20 sm:py-28 text-center">
            @if($settings->hero_eyebrow)
                <p class="font-mono text-xs tracking-widest text-cidst-muted uppercase mb-5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-cidst-red mr-2 align-middle"></span>
                    {{ $settings->hero_eyebrow }}
                </p>
            @endif

            <h1 class="font-display text-4xl sm:text-6xl font-semibold text-cidst-ink leading-[1.1] max-w-3xl mx-auto">
                {{ $settings->hero_title }}
            </h1>

            @if($settings->hero_subtitle)
                <p class="mt-6 text-lg text-cidst-muted max-w-xl mx-auto leading-relaxed">
                    {{ $settings->hero_subtitle }}
                </p>
            @endif

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                @if($settings->cta_primary_label && $settings->cta_primary_target)
                    <a href="{{ \Illuminate\Support\Facades\Route::has($settings->cta_primary_target) ? route($settings->cta_primary_target) : $settings->cta_primary_target }}"
                       class="bg-cidst-red text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                        {{ $settings->cta_primary_label }}
                    </a>
                @endif

                @if($settings->cta_secondary_label && $settings->cta_secondary_target)
                    <a href="{{ \Illuminate\Support\Facades\Route::has($settings->cta_secondary_target) ? route($settings->cta_secondary_target) : $settings->cta_secondary_target }}"
                       class="border border-cidst-border text-cidst-ink px-6 py-3 rounded-md text-sm font-medium hover:bg-cidst-surface transition-colors">
                        {{ $settings->cta_secondary_label }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- VALEURS --}}
    <section class="py-16 border-t border-cidst-border">
        <p class="font-mono text-xs text-cidst-muted mb-8">// Notre mission</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Fiable</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Une information vérifiée, issue de sources scientifiques reconnues.
                </p>
            </div>
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Structurée</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Une documentation organisée, facile à explorer et à retrouver.
                </p>
            </div>
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Accessible</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Écrite pour être comprise, au-delà du seul cercle des spécialistes.
                </p>
            </div>
        </div>
    </section>

</x-layout>