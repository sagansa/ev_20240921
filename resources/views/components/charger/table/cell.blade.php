@props([
    'class' => '',
    'variant' => 'muted',
    'wrap' => false,
])

@php
    $variants = [
        'muted' => 'text-gray-500 dark:text-gray-400',
        'normal' => 'text-gray-700 dark:text-gray-300',
        'emphasis' => 'text-gray-900 dark:text-gray-100 font-medium',
        'plain' => '',
    ];

    $baseClasses = [
        'px-3',
        'py-4',
        'text-sm',
        $wrap ? 'align-top whitespace-normal break-words' : 'whitespace-nowrap',
        $variants[$variant] ?? $variants['muted'],
        $class,
    ];
@endphp

<td {{ $attributes->merge(['class' => trim(implode(' ', array_filter($baseClasses)))]) }}>
    {{ $slot }}
</td>
