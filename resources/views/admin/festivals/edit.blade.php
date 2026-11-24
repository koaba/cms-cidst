@php
    $on = fn ($value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN);
@endphp
<x-admin.layout title="Fêtes">
    <h1 class="text-2xl font-bold mb-2">Fêtes</h1>
    <p class="text-sm text-base-content/70 mb-6 max-w-2xl">
        Pendant la période d'une fête, le site public prend automatiquement les couleurs de la fête et, si vous le souhaitez, un décor (bandeau d'icônes et icône sur le logo). Rien ne change dans l'administration, et votre thème revient dès la fin de la période.
    </p>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-cidst-danger-soft text-cidst-danger-content p-3 rounded mb-4" role="alert">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @unless($envEnabled)
        <div class="alert alert-warning mb-4" role="status">
            Les fêtes sont coupées par le fichier .env (FESTIVALS_ENABLED=false) : les réglages ci-dessous n'ont aucun effet tant que cette ligne n'est pas retirée.
        </div>
    @endunless

    <form action="{{ route('admin.festivals.update') }}" method="POST" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <fieldset class="border rounded p-4 space-y-3">
            <legend class="font-medium px-1">Général</legend>
            <input type="hidden" name="festivals_enabled" value="0">
            <label class="flex items-center gap-2 font-medium">
                <input type="checkbox" name="festivals_enabled" value="1" class="h-4 w-4" {{ $on(old('festivals_enabled', $enabled ? 1 : 0)) ? 'checked' : '' }}>
                Activer les fêtes automatiques
            </label>
            <p class="text-sm opacity-70">Décochez pour couper d'un coup tous les thèmes et décors de fête, sans toucher aux réglages de chaque fête.</p>
        </fieldset>

        <fieldset class="border rounded p-4 space-y-3">
            <legend class="font-medium px-1">Aperçu à une date (réservé au Super Admin)</legend>
            <div>
                <label for="festivals_simulated_on" class="block font-medium mb-1">Voir le site comme s'il était le…</label>
                <input type="date" id="festivals_simulated_on" name="festivals_simulated_on" value="{{ old('festivals_simulated_on', $simulatedOn?->format('Y-m-d')) }}" class="border rounded p-2">
            </div>
            <p class="text-sm opacity-70">
                Seul vous, connecté en Super Admin, voyez le site public à cette date. Les visiteurs voient toujours la vraie date. Videz le champ pour arrêter l'aperçu.
            </p>
            @if($simulatedOn)
                <div class="alert alert-info" role="status">
                    Aperçu actif : le site public s'affiche pour vous comme le {{ $simulatedOn->format('d/m/Y') }}.
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="link">Voir le site</a>
                </div>
            @endif
        </fieldset>

        @foreach($festivals as $key => $festival)
            @php $o = $festival['options']; @endphp
            <fieldset class="border rounded p-4 space-y-4" id="fete-{{ $key }}">
                <legend class="font-medium px-1">{{ $festival['label'] }}</legend>
                <p class="text-sm opacity-70">
                    Prochaine période : du {{ $festival['window'][0]->format('d/m/Y') }} au {{ $festival['window'][1]->format('d/m/Y') }}.
                </p>

                <input type="hidden" name="festivals[{{ $key }}][enabled]" value="0">
                <label class="flex items-center gap-2 font-medium">
                    <input type="checkbox" name="festivals[{{ $key }}][enabled]" value="1" class="h-4 w-4" {{ $on(old("festivals.$key.enabled", $o['enabled'] ? 1 : 0)) ? 'checked' : '' }}>
                    Activer cette fête
                </label>

                <div>
                    <label for="fete-{{ $key }}-placement" class="block font-medium mb-1">Emplacement du décor</label>
                    <select id="fete-{{ $key }}-placement" name="festivals[{{ $key }}][placement]" class="border rounded p-2">
                        @foreach($placements as $value => $label)
                            <option value="{{ $value }}" @selected(old("festivals.$key.placement", $o['placement']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fete-{{ $key }}-logo" class="block font-medium mb-1">Icône sur le logo</label>
                    <select id="fete-{{ $key }}-logo" name="festivals[{{ $key }}][logo]" class="border rounded p-2">
                        @foreach($icons as $value => $label)
                            <option value="{{ $value }}" @selected(old("festivals.$key.logo", $o['logo']) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="font-medium mb-1">Icônes du bandeau</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @for($i = 0; $i < $slots; $i++)
                            <div>
                                <label for="fete-{{ $key }}-strip-{{ $i }}" class="block text-xs opacity-70">Icône {{ $i + 1 }}{{ $i < 4 ? '' : ' (bureau)' }}</label>
                                <select id="fete-{{ $key }}-strip-{{ $i }}" name="festivals[{{ $key }}][strip][{{ $i }}]" class="w-full border rounded p-2">
                                    <option value="">—</option>
                                    @foreach($icons as $value => $label)
                                        <option value="{{ $value }}" @selected(old("festivals.$key.strip.$i", $o['strip'][$i] ?? '') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endfor
                    </div>
                    <p class="text-xs opacity-70 mt-1">Les 4 premières icônes restent visibles sur mobile. Une case vide est ignorée ; si vous en choisissez moins de {{ $slots }}, la liste se répète.</p>
                </div>

                <input type="hidden" name="festivals[{{ $key }}][animated]" value="0">
                <label class="flex items-center gap-2 font-medium">
                    <input type="checkbox" name="festivals[{{ $key }}][animated]" value="1" class="h-4 w-4" {{ $on(old("festivals.$key.animated", $o['animated'] ? 1 : 0)) ? 'checked' : '' }}>
                    Animer les icônes (léger balancement, coupé pour les visiteurs qui réduisent les animations)
                </label>

                <div>
                    <p class="text-xs opacity-70 mb-1">Aperçu des réglages enregistrés :</p>
                    <div class="flex items-center gap-3 border rounded p-2 w-fit" aria-hidden="true">
                        @include('partials.festival.icons.'.$o['logo'], ['class' => 'h-7 w-7'])
                        <span class="opacity-40">|</span>
                        @foreach($festival['preview'] as $icon)
                            @include('partials.festival.icons.'.$icon, ['class' => 'h-6 w-auto'])
                        @endforeach
                    </div>
                </div>
            </fieldset>
        @endforeach

        <div class="flex items-center gap-3">
            <x-admin.button type="submit">Enregistrer</x-admin.button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>
