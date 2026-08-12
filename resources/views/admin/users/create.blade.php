<x-admin.layout title="Nouvel utilisateur">
    <h1 class="text-xl font-bold mb-6">Nouvel utilisateur</h1>

    <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white p-6 rounded shadow max-w-md">
        @csrf

        <div class="mb-4">
            <label class="block mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered w-full">
            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered w-full">
            @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block mb-1">Mot de passe</label>
            <input type="password" name="password" class="input input-bordered w-full">
            @error('password') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block mb-1">Rôle</label>
            <select name="role" class="select select-bordered w-full">
                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ $role }}</option>
                @endforeach
            </select>
            @error('role') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary text-white">Créer</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
</x-admin.layout>