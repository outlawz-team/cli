@props([
    'href' => '#',
    'variant' => 'default',
    'external' => false,
    'weight' => 'normal',
])

@php
    $variantClasses = match ($variant) {
        'default' => $external ? 'text-blue-600 hover:text-blue-800' : 'text-blue-600 hover:text-blue-800 underline decoration-1 underline-offset-2 hover:decoration-2',
        'unstyled' => $external ? 'text-inherit' : 'text-inherit no-underline hover:underline',
        default => $external ? 'text-blue-600 hover:text-blue-800' : 'text-blue-600 hover:text-blue-800 underline decoration-1 underline-offset-2 hover:decoration-2'
    };

    $textClasses = $external ? match ($variant) {
        'default' => 'underline decoration-1 underline-offset-2 hover:decoration-2',
        'unstyled' => 'no-underline hover:underline',
        default => 'underline decoration-1 underline-offset-2 hover:decoration-2'
    } : '';

    $weightClasses = match ($weight) {
        'light' => 'font-light',
        'normal' => 'font-normal',
        'medium' => 'font-medium',
        'semibold' => 'font-semibold',
        'bold' => 'font-bold',
        default => 'font-normal'
    };

    $classes = [
        $variantClasses,
        $weightClasses
    ];

    $linkAttributes = [
        'href' => $href,
    ];

    if ($external) {
        $linkAttributes['target'] = '_blank';
        $linkAttributes['rel'] = 'noopener noreferrer';
    }
@endphp

<a
    @foreach ($linkAttributes as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
>
    @if (! $external)
        {{ $slot }}
    @endif

    @if ($external)
        <span class="{{ Arr::toCssClasses([$textClasses]) }}">{{ $slot }}</span>
        <svg class="inline size-3 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
        </svg>
    @endif
</a>
