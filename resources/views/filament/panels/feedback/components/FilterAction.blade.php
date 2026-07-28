@php
    use Filament\Support\Enums\MaxWidth;

    $livewire = $action->getLivewire();
    $activeFiltersCount = collect($livewire->filters ?? [])
        ->filter(fn ($value) => filled($value))
        ->count();
@endphp

<x-filament::dropdown
    placement="bottom-end"
    shift
    :width="MaxWidth::ExtraSmall"
    wire:key="{{ $livewire->getId() }}.dashboard.filters"
    class="fi-ac-filter-action"
>
    <x-slot name="trigger">
        <x-filament::icon-button
            :badge="$activeFiltersCount"
            color="gray"
            icon="heroicon-m-funnel"
            :label="$action->getLabel()"
        />
    </x-slot>

    <div class="fi-ta-filters grid gap-y-4 p-6">
        <div class="flex items-center justify-between">
            <h4 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                {{ __('filament-tables::table.filters.heading') }}
            </h4>

            <div>
                <x-filament::link
                    color="danger"
                    tag="button"
                    wire:click="resetDashboardFilters"
                    wire:loading.remove.delay.default
                    wire:target="resetDashboardFilters"
                >
                    {{ __('filament-tables::table.filters.actions.reset.label') }}
                </x-filament::link>

                <x-filament::loading-indicator
                    wire:loading.delay.default
                    wire:target="filters,resetDashboardFilters"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                />
            </div>
        </div>

        {{ $livewire->filtersForm }}
    </div>
</x-filament::dropdown>
