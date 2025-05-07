@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => 'block py-1.5 pr-10 pl-3 mt-2 w-full text-gray-900 dark:text-white dark:bg-gray-800 rounded-md border-0 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6']) }}>
    {{ $slot }}
</select>
