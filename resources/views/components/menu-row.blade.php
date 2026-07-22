<tr class="border-b {{ $depth > 0 ? 'bg-gray-50' : '' }}">
    <td class="p-2" style="padding-left: {{ 0.5 + $depth * 2 }}rem">
        {{ $depth > 0 ? str_repeat('-- ', $depth) : '' }}{{ $menu->label }}
    </td>
    <td class="p-2 text-sm text-gray-500">{{ $menu->target }}</td>
    <td class="p-2">{{ $menu->order }}</td>
    <td class="p-2">
        <span class="{{ $menu->is_active ? 'text-green-600' : 'text-gray-400' }}">
            {{ $menu->is_active ? 'Oui' : 'Non' }}
        </span>
    </td>
    <td class="p-2 flex gap-2">
        <a href="{{ route('admin.menus.edit', $menu) }}" class="text-blue-600">Modifier</a>
        <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST"
              onsubmit="return confirm('Supprimer ce menu ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600">Supprimer</button>
        </form>
    </td>
</tr>
@foreach($menu->children as $child)
    <x-menu-row :menu="$child" :depth="$depth + 1" />
@endforeach