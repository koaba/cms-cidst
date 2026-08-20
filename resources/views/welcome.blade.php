<x-layout title="CIDST - Centre d'Information et de Documentation Scientifique et Technique">

    @php
    $settings = \App\Models\SiteSetting::current();
@endphp

    <section class="relative overflow-hidden -mx-4 sm:-mx-6 px-4 sm:px-6 -mt-10 pt-10">

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
                @php
                    $eyebrowSizes = [
                        'xs' => 'text-xs',
                        'sm' => 'text-sm',
                        'base' => 'text-base',
                        'lg' => 'text-lg',
                        'xl' => 'text-xl',
                    ];
                    $eyebrowSizeClass = $eyebrowSizes[$settings->hero_eyebrow_size] ?? 'text-xs';
                @endphp
                <p class="font-mono {{ $eyebrowSizeClass }} tracking-widest text-cidst-muted uppercase mb-5">
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

    <section class="py-16 border-t border-cidst-border">
        <p class="font-mono text-xs text-cidst-muted mb-8">// Notre mission</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Fiable</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Une information verifiee, issue de sources scientifiques reconnues.
                </p>
            </div>
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Structuree</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Une documentation organisee, facile a explorer et a retrouver.
                </p>
            </div>
            <div class="bg-cidst-surface rounded-lg p-6 border border-cidst-border">
                <h3 class="font-display font-semibold text-cidst-ink mb-2">Accessible</h3>
                <p class="text-sm text-cidst-muted leading-relaxed">
                    Ecrite pour etre comprise, au-dela du seul cercle des specialistes.
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 border-t border-cidst-border">
        @php
            $homeSliders = \App\Models\Slider::where('is_active', true)
                ->orderBy('order')
                ->take(5)
                ->get();
        @endphp

        @if($homeSliders->isNotEmpty())
            <p class="font-mono text-xs text-cidst-muted mb-8">// Actualités en images</p>

            <div class="relative rounded-lg overflow-hidden border border-cidst-border" id="home-slider">
                <div class="relative h-64 sm:h-80">
                    @foreach($homeSliders as $index => $slider)
                        <div
                            class="home-slider-slide absolute inset-0 transition-opacity duration-700 {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}"
                            data-index="{{ $index }}"
                        >
                            @if($slider->link_url)
                                <a href="{{ $slider->link_url }}" class="block w-full h-full">
                            @endif

                            <img
                                src="{{ Storage::url($slider->image) }}"
                                alt="{{ $slider->title }}"
                                class="w-full h-full object-cover"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            >

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-6">
                                <h3 class="font-display text-white text-lg font-semibold">{{ $slider->title }}</h3>
                                @if($slider->subtitle)
                                    <p class="text-white/80 text-sm mt-1">{{ $slider->subtitle }}</p>
                                @endif
                            </div>

                            @if($slider->link_url)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($homeSliders->count() > 1)
                    <div class="absolute bottom-3 right-4 z-20 flex gap-2">
                        @foreach($homeSliders as $index => $slider)
                            <button
                                type="button"
                                class="home-slider-dot w-2 h-2 rounded-full transition-colors {{ $index === 0 ? 'bg-white' : 'bg-white/40' }}"
                                data-index="{{ $index }}"
                                aria-label="Aller à la diapositive {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </section>

@if(isset($homeSliders) && $homeSliders->count() > 1)
        <script>
            (function () {
                const container = document.getElementById('home-slider');
                if (!container) return;

                const slides = container.querySelectorAll('.home-slider-slide');
                const dots = container.querySelectorAll('.home-slider-dot');
                let current = 0;
                let paused = false;
                const total = slides.length;

                function show(index) {
                    slides.forEach((slide, i) => {
                        slide.classList.toggle('opacity-100', i === index);
                        slide.classList.toggle('z-10', i === index);
                        slide.classList.toggle('opacity-0', i !== index);
                        slide.classList.toggle('z-0', i !== index);
                    });
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('bg-white', i === index);
                        dot.classList.toggle('bg-white/40', i !== index);
                    });
                    current = index;
                }

                dots.forEach(dot => {
                    dot.addEventListener('click', () => show(parseInt(dot.dataset.index, 10)));
                });

                container.addEventListener('mouseenter', () => paused = true);
                container.addEventListener('mouseleave', () => paused = false);

                setInterval(() => {
                    if (!paused) show((current + 1) % total);
                }, 5000);
            })();
        </script>
    @endif

</x-layout>