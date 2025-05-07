@props(['class' => ''])

<td
    {{ $attributes->merge(['class' => 'px-3 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap ' . $class]) }}>
    {{ $slot }}
</td>
