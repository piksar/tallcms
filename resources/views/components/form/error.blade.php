@props([
    'field' => null,
])

@if($field)
<p
    x-show="errors.{{ $field }}"
    x-cloak
    {{ $attributes->merge(['class' => 'mt-1 text-sm text-red-600']) }}
    x-text="errors.{{ $field }} ? errors.{{ $field }}[0] : ''"
></p>
@else
{{ $slot }}
@endif
