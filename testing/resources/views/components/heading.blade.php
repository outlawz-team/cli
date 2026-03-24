@props([
    'level' => 'h2',
    'size' => null,
    'color' => 'default',
])

@php
    $tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $level : 'h2';

    // Default size based on heading level if not specified
    $defaultSize = match ($level) {
        'h1' => 'xl',
        'h2' => 'lg',
        'h3' => 'md',
        'h4' => 'sm',
        'h5' => 'xs',
        'h6' => 'xs',
        default => 'md'
    };

    $size = $size ?? $defaultSize;

    $sizeClasses = match ($size) {
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'text-xl',
        'xl' => 'text-2xl',
        '2xl' => 'text-3xl',
        '3xl' => 'text-4xl',
        default => 'text-base'
    };

    $colorClasses = match ($color) {
        'default' => 'text-black',
        'black' => 'text-black',
        'white' => 'text-white',
        'muted' => 'text-black',
        default => 'text-black'
    };

    $classes = [
        'font-semibold',
        $sizeClasses,
        $colorClasses
    ];
@endphp

<{{ $tag }}
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
>
    {{ $slot }}
</{{ $tag }}>
