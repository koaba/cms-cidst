@props(['action', 'message' => 'Supprimer ?'])
@php($needsCode = ! auth()->user()->hasRole(\App\Enums\UserRole::SuperAdmin))
<form method="POST" action="{{ $action }}" {{ $attributes->merge(['class' => 'inline']) }}
      onsubmit="return confirm({{ \Illuminate\Support\Js::from($message) }}){{ $needsCode ? " && !!(this.delete_code.value = prompt('Code de suppression') || '')" : '' }}">
    @csrf
    @method('DELETE')
    @if ($needsCode)<input type="hidden" name="delete_code">@endif
    {{ $slot }}
</form>
