<x-admin.layout>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ $message->subject ?: 'Message de '.$message->name }}</h1>
        <a href="{{ route('admin.messages.index') }}" class="text-blue-600">&larr; Retour</a>
    </div>

    <dl class="mb-6 space-y-1">
        <div><dt class="inline font-medium">De :</dt> <dd class="inline">{{ $message->name }} &lt;{{ $message->email }}&gt;</dd></div>
        <div><dt class="inline font-medium">Re&ccedil;u le :</dt> <dd class="inline">{{ $message->created_at->format('d/m/Y H:i') }}</dd></div>
    </dl>

    <div class="border rounded p-4 mb-6 whitespace-pre-line">{{ $message->message }}</div>

    <x-admin.button href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Votre message')) }}">R&eacute;pondre</x-admin.button>
</x-admin.layout>
