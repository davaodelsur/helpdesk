@php
    $id = $action->getId();
    $isDisabled = $action->isDisabled();
    $name = $action->getName();
    $options = $action->getOptions();
    $placeholder = $action->getPlaceholder(); // Matches the "All" in your image
@endphp

<div
    class="fi-ac-select-action relative min-w-[200px] max-w-sm place"
    x-data="{
        isOpen: false,
        search: '',
        selectedLabel: '{{ $placeholder }}',
        selectOption(value, label) {
            this.selectedLabel = label;
            this.isOpen = false;
            this.search = '';
            $wire.mountAction('{{ $name }}', { value: value });
        }
    }"
    x-on:click.outside="isOpen = false"
    x-on:keydown.escape.window="isOpen = false"
>

    <button
        type="button"
        id="{{ $id }}"
        @disabled($isDisabled)
        x-on:click="isOpen = !isOpen; if (isOpen) $nextTick(() => $refs.searchInput.focus())"
        :class="isOpen
            ? 'border-orange-500 ring-1 ring-orange-500 dark:border-orange-500 dark:ring-orange-500'
            : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500'"
        class="flex w-full items-center justify-between rounded-md border bg-white px-3 py-2 text-sm shadow-sm transition duration-75 focus:outline-none focus:ring-1 focus:ring-orange-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-70 dark:bg-gray-800"
    >
        <span
            x-text="selectedLabel"
            class="block truncate text-gray-900 dark:text-white"
            :class="selectedLabel === '{{ $placeholder }}' ? 'text-gray-500 dark:text-gray-400' : ''"
        ></span>

        <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-10 mt-1 w-full overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-white/10"
        style="display: none;"
    >
        <div class="p-2">
            <input
                type="search"
                x-ref="searchInput"
                x-model="search"
                placeholder="Start typing to search..."
                class="block w-full bg-transparent px-2 py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                x-on:keydown.stop
                x-on:click.stop
            >
        </div>

        <ul class="max-h-60 overflow-auto p-1 text-sm">
            @foreach ($options as $value => $label)
                <li
                    x-show="search === '' || '{{ addslashes(strtolower($label)) }}'.includes(search.toLowerCase())"
                    x-on:click="selectOption('{{ $value }}', '{{ addslashes($label) }}')"
                    class="relative cursor-pointer select-none rounded-md px-3 py-2 text-gray-800 outline-none transition hover:bg-gray-100 focus:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
                >
                    <span class="block truncate font-normal">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
