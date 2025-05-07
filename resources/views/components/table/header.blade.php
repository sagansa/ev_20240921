@props(['class' => ''])

<th
    {{ $attributes->merge(['class' => 'py-3.5 pr-3 pl-4 text-sm font-semibold text-left text-gray-900 dark:text-gray-100 sm:pl-0 ' . $class]) }}>
    {{ $slot }}
</th>
