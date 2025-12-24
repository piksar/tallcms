@props([
    'rows' => 5,
])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->merge([
        'class' => 'w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 bg-white',
    ]) }}
>{{ $slot }}</textarea>
