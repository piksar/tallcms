<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('tallcms::widgets.dashboard_scope.heading') }}
        </x-slot>

        <x-slot name="description">
            {{ __('tallcms::widgets.dashboard_scope.description') }}
        </x-slot>

        <select
            wire:model.live="selected"
            class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-base-content"
        >
            @if ($this->isSuperAdmin())
                <option value="__all_sites__">{{ __('tallcms::widgets.dashboard_scope.all_sites') }}</option>
            @endif

            @foreach ($this->sitesForUser as $site)
                <option value="{{ $site->id }}">{{ $site->name }}</option>
            @endforeach
        </select>
    </x-filament::section>
</x-filament-widgets::widget>
