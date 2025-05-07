@props(['name'])

<th scope="col" class="px-3 py-3.5 text-sm font-semibold text-left text-gray-900 dark:text-gray-100">
    <a href="{{ route('chargers', ['sort' => $name, 'direction' => request('sort') == $name && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
        class="inline-flex group">
        {{ $slot }}
        <span class="flex-none ml-2 text-gray-400 rounded group-hover:visible group-focus:visible">
            @if (request('sort') == $name)
                @if (request('direction') == 'asc')
                    &#x25B2;
                @else
                    &#x25BC;
                @endif
            @else
                &#x25B2;
            @endif
        </span>
    </a>
</th>
