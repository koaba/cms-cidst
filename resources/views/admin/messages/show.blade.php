<x-admin.layout :title="$message->subject ?: 'Message de '.$message->name">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ $message->subject ?: 'Message de '.$message->name }}</h1>
        <a href="{{ route('admin.messages.index') }}" class="text-blue-600">&larr; Retour</a>
    </div>

    <dl class="mb-6 space-y-1">
        <div><dt class="inline font-medium">De :</dt> <dd class="inline">{{ $message->name }} &lt;{{ $message->email }}&gt;</dd></div>
        <div><dt class="inline font-medium">Re&ccedil;u le :</dt> <dd class="inline">{{ $message->created_at->format('d/m/Y H:i') }}</dd></div>
    </dl>

    <div class="border rounded p-4 mb-6 whitespace-pre-line">{{ $message->message }}</div>

    @if ($message->attachment_link && str_starts_with($message->attachment_link, 'https://'))
        <div class="border rounded p-4 mb-6">
            <p class="font-medium mb-1">Document partag&eacute; par le visiteur</p>
            <p class="text-sm break-all mb-2">{{ $message->attachment_link }}</p>
            <a href="{{ $message->attachment_link }}" target="_blank" rel="noopener noreferrer nofollow" class="link link-primary">Ouvrir le lien (nouvel onglet)</a>
            <p class="text-sm text-base-content/60 mt-2">N'ouvrez ce lien que si vous connaissez l'exp&eacute;diteur.</p>
        </div>
    @endif

    @if ($message->attachments->isNotEmpty())
        <div class="border rounded p-4 mb-6">
            <p class="font-medium mb-2">Images jointes ({{ $message->attachments->count() }})</p>
            <ul class="space-y-1">
                @foreach ($message->attachments as $file)
                    <li class="flex flex-wrap items-center gap-x-3">
                        <span class="break-all">{{ $file->original_name }}</span>
                        <span class="text-sm text-base-content/60">{{ number_format($file->size / 1024, 0, ',', ' ') }} Ko</span>
                        <a href="{{ route('admin.messages.attachments.show', [$message, $file]) }}" class="link link-primary">T&eacute;l&eacute;charger</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-admin.button href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Votre message')) }}">R&eacute;pondre</x-admin.button>
</x-admin.layout>
