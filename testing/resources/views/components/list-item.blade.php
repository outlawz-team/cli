@props([
    'spacing' => 'normal',
])

@php
    $spacingClasses = match ($spacing) {
        'tight' => 'mb-1',
        'normal' => 'mb-2',
        'loose' => 'mb-4',
        default => 'mb-2'
    };

    $classes = [
        $spacingClasses
    ];
@endphp

<li
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
>
    {{ $slot }}
</li>