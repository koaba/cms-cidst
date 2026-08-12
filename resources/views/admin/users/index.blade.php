<x-admin.layout title="Utilisateurs">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-bold">Utilisateurs</h1>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary text-white">Nouvel utilisateur</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <table class="table w-full bg-white rounded shadow">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Confirmer la suppression ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-error btn-sm text-white">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-admin.layout>