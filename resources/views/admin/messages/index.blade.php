<x-admin.layout>
    <h1 class="text-2xl font-bold mb-6">Messages</h1>

    <table class="w-full border-collapse">
        <thead>
            <tr class="text-left border-b">
                <th class="p-2">Statut</th>
                <th class="p-2">Nom</th>
                <th class="p-2">Email</th>
                <th class="p-2">Sujet</th>
                <th class="p-2">Re&ccedil;u le</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($messages as $item)
            <tr class="border-b {{ $item->read_at ? '' : 'font-semibold' }}">
                <td class="p-2">
                    <span class="{{ $item->read_at ? 'text-gray-400' : 'text-green-600' }}">{{ $item->read_at ? 'Lu' : 'Nouveau' }}</span>
                </td>
                <td class="p-2">{{ $item->name }}</td>
                <td class="p-2">{{ $item->email }}</td>
                <td class="p-2">{{ Str::limit($item->subject ?: '-', 50) }}</td>
                <td class="p-2">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                <td class="p-2"><a href="{{ route('admin.messages.show', $item) }}" class="text-blue-600">Lire</a></td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-4 text-center text-gray-500">Aucun message</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $messages->links() }}</div>
</x-admin.layout>
