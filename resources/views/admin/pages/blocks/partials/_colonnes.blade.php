<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
    <input type="text" name="title" value="{{ $data['title'] ?? old('title') }}" class="w-full border rounded p-2 text-sm">
    @error('title')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium mb-1">Nombre de colonnes</label>

    @if(isset($data))
        {{-- Édition : figé après création pour ne pas perdre le contenu des colonnes existantes. --}}
        <input type="hidden" name="column_count" value="{{ $data['column_count'] }}">
        <p class="text-sm text-gray-700 border rounded p-2 bg-gray-50">
            {{ $data['column_count'] }} colonnes
            <span class="text-gray-400">(non modifiable après création)</span>
        </p>
    @else
        <select name="column_count" class="w-full border rounded p-2 text-sm">
            @for($i = 2; $i <= 6; $i++)
                <option value="{{ $i }}" @selected((int) old('column_count', 2) === $i)>{{ $i }} colonnes</option>
            @endfor
        </select>
        @error('column_count')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    @endif
</div>

@if(isset($data))
    <p class="text-sm text-gray-500 border-t pt-3 mt-3">
        Le contenu de chaque colonne se gère depuis la page de modification du bloc, une fois celui-ci enregistré.
    </p>
@endif