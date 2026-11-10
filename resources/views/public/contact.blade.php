<x-layout :sidebar="false" seo-page="contact">
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-display font-bold mb-6 text-cidst-ink">Contact</h1>
        @if (session('success'))
            <div class="mb-6 rounded border border-green-300 bg-green-50 px-4 py-3 text-green-800">Message envoy&eacute;.</div>
        @endif
        <form method="POST" action="{{ route('contact.store') }}" enctype="multipart/form-data" class="space-y-4">
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
            @if ($imagesEnabled)
                <div>
                    <label for="images" class="block font-medium mb-1 text-cidst-ink">Images <span class="font-normal text-cidst-muted">(facultatif)</span></label>
                    <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" aria-describedby="images_help" class="block w-full text-sm text-cidst-ink file:mr-3 file:rounded file:border-0 file:bg-cidst-bg file:px-3 file:py-2 file:font-medium file:text-cidst-ink">
                    <p id="images_help" class="mt-1 text-sm text-cidst-muted">JPG, PNG ou WebP, {{ $imagesMaxFiles }} images au maximum, {{ $imagesMaxMb }} Mo chacune. Pour un PDF ou un autre document, utilisez le lien ci-dessous.</p>
                    @error('images') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    @foreach (collect($errors->get('images.*'))->flatten()->unique() as $error)
                        <p class="text-red-600 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <div>
                <label for="attachment_link" class="block font-medium mb-1 text-cidst-ink">Lien vers un document <span class="font-normal text-cidst-muted">(facultatif)</span></label>
                <input id="attachment_link" type="url" name="attachment_link" value="{{ old('attachment_link') }}" maxlength="500" placeholder="https://drive.google.com/..." aria-describedby="attachment_link_help" class="w-full border border-cidst-border rounded p-2">
                <p id="attachment_link_help" class="mt-1 text-sm text-cidst-muted">Pour joindre un fichier (PDF, document, etc.), d&eacute;posez-le sur Google Drive, Dropbox ou OneDrive, puis collez ici le lien de partage. Nous pourrons ainsi le t&eacute;l&eacute;charger sur notre ordinateur, sans passer par le site.</p>
                @error('attachment_link') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="inline-flex items-center rounded bg-cidst-red px-5 py-2 font-medium text-white hover:opacity-90">Envoyer</button>
        </form>
    </div>
</x-layout>
