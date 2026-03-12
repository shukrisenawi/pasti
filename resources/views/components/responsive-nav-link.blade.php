@props(['active'])

@php
$classes = ($active ?? false)
            ? 'btn btn-sm btn-primary w-full justify-start'
            : 'btn btn-sm btn-ghost w-full justify-start';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
