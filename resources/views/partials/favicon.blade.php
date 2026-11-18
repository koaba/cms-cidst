{{-- Favicon unique (logo CIDST, 48 px, moins de 1 Ko). ?v= force le rechargement quand le fichier change. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="48x48">
