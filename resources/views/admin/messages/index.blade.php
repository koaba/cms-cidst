<x-admin.layout title="Messages">
    <h1 class="text-2xl font-bold mb-6">Messages</h1>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

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
                    <span class="{{ $item->read_at ? 'text-base-content/50' : 'text-green-600' }}">{{ $item->read_at ? 'Lu' : 'Nouveau' }}</span>
                </td>
                <td class="p-2">{{ $item->name }}</td>
                <td class="p-2">{{ $item->email }}</td>
                <td class="p-2">{{ Str::limit($item->subject ?: '-', 50) }}@if ($item->attachments_count) <span title="Images jointes">&#128206; {{ $item->attachments_count }}</span>@endif</td>
                <td class="p-2">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                <td class="p-2">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.messages.show', $item) }}" class="text-blue-600">Lire</a>
                        <form method="POST" action="{{ route('admin.messages.destroy', $item) }}" onsubmit="return confirm('Supprimer ce message et ses images ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-4 text-center text-base-content/60">Aucun message</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $messages->links() }}</div>
</x-admin.layout>
