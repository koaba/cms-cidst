@props(['term', 'text'])
<a href="{{ route('admin.guide') }}#guide-glossaire" data-help="{{ $term }}" title="{{ $text }}" aria-label="Aide : {{ $text }}" class="badge badge-ghost badge-sm align-middle no-underline">?</a>
