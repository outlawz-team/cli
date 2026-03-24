@props([
    'type' => 'ul', // 'ul' or 'ol'
    'spacing' => 'normal',
    'style' => 'default',
])

@php
    $tag = in_array($type, ['ul', 'ol']) ? $type : 'ul';

    $spacingClasses = match ($spacing) {
        'tight' => 'space-y-1',
        'normal' => 'space-y-2',
        'loose' => 'space-y-4',
        default => 'space-y-2'
    };

    $styleClasses = match ($style) {
        'default' => $tag === 'ul' ? 'list-disc' : 'list-decimal',
        'none' => 'list-none',
        'inside' => $tag === 'ul' ? 'list-disc list-inside' : 'list-decimal list-inside',
        'outside' => $tag === 'ul' ? 'list-disc list-outside ml-6' : 'list-decimal list-outside ml-6',
        default => $tag === 'ul' ? 'list-disc' : 'list-decimal'
    };

    $classes = [
        $spacingClasses,
        $styleClasses,
        $style === 'default' ? 'ml-6' : ''
    ];
@endphp

<{{ $tag }}
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
>
    {{ $slot }}
</{{ $tag }}>