<x-layout :sidebar="false" title="Contact">
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">Contact</h1>
        @if (session('success'))
            <div class="mb-6 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-800">Message envoy&eacute;.</div>
        @endif
        <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div>
                <label for="name" class="block font-medium mb-1 text-cidst-ink">Nom</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="w-full border border-cidst-border rounded p-2">
                @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="block font-medium mb-1 text-cidst-ink">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full border border-cidst-border rounded p-2">
                @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="subject" class="block font-medium mb-1 text-cidst-ink">Sujet</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" class="w-full border border-cidst-border rounded p-2">
            </div>
            <div>
                <label for="message" class="block font-medium mb-1 text-cidst-ink">Message</label>
                <textarea id="message" name="message" rows="6" required class="w-full border border-cidst-border rounded p-2">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="inline-flex items-center rounded bg-cidst-red px-5 py-2 font-medium text-white hover:opacity-90">Envoyer</button>
        </form>
    </div>
</x-layout>
