<tr class="border-b {{ $depth > 0 ? 'bg-base-200' : '' }}">
    <td class="p-2" style="padding-left: {{ 0.5 + $depth * 2 }}rem">
        {{ $depth > 0 ? str_repeat('-- ', $depth) : '' }}{{ $menu->label }}
    </td>
    <td class="p-2 text-sm text-base-content/60">{{ $menu->target }}</td>
    <td class="p-2">{{ $menu->order }}</td>
    <td class="p-2">
        <span class="{{ $menu->is_active ? 'text-cidst-success' : 'text-base-content/50' }}">
            {{ $menu->is_active ? 'Oui' : 'Non' }}
        </span>
    </td>
    <td class="p-2 flex gap-2">
        <a href="{{ route('admin.menus.edit', $menu) }}" class="text-cidst-primary">Modifier</a>
        <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST"
              onsubmit="return confirm('Supprimer ce menu ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-cidst-danger">Supprimer</button>
        </form>
    </td>
</tr>
@foreach($menu->children as $child)
    <x-menu-row :menu="$child" :depth="$depth + 1" />
@endforeach