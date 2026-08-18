<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Documents PDF</h1>
        <x-admin.button href="{{ route('admin.pdf-documents.create') }}">+ Nouveau document</x-admin.button>
    </div>
    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr class="text-left border-b">
                <th class="p-3">Titre</th>
                <th class="p-3">Catégorie</th>
                <th class="p-3">PDF</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documents as $document)
                <tr class="border-b">
                    <td class="p-3">{{ $document->title }}</td>
                    <td class="p-3">{{ $document->category->name }}</td>
                    <td class="p-3">{{ $document->pdfs->count() }}</td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.pdf-documents.edit', $document) }}" class="text-blue-600">Modifier</a>
                        <form method="POST" action="{{ route('admin.pdf-documents.destroy', $document) }}" onsubmit="return confirm('Supprimer ce document ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        {{ $documents->links() }}
    </div>
</x-admin.layout>