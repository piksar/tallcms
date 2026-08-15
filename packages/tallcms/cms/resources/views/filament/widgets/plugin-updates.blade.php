<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="rounded-full bg-warning-100 dark:bg-warning-500/20 p-3">
                    <x-heroicon-o-arrow-path class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ __('tallcms::widgets.plugin_updates.heading') }}
                    </h3>
                    <button
                        type="button"
                        wire:click="refresh"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-50"
                    >
                        <x-heroicon-m-arrow-path class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" />
                        <span wire:loading.remove wire:target="refresh">{{ __('tallcms::widgets.plugin_updates.refresh') }}</span>
                        <span wire:loading wire:target="refresh">{{ __('tallcms::widgets.plugin_updates.checking') }}</span>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if (count($updates) === 1)
                        {{ __('tallcms::widgets.plugin_updates.summary_one') }}
                    @else
                        {{ __('tallcms::widgets.plugin_updates.summary_many', ['count' => count($updates)]) }}
                    @endif
                </p>

                <div class="mt-3 space-y-2">
                    @foreach($updates as $slug => $update)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $update['plugin_name'] }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $update['current_version'] }} &rarr; {{ $update['latest_version'] }}
                                </span>
                            </div>
                            @if($update['download_url'])
                                <a
                                    href="{{ $update['download_url'] }}"
                                    target="_blank"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ __('tallcms::widgets.plugin_updates.download') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <a
                        href="{{ tallcms_panel_route('pages.plugin-manager') }}"
                        class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                    >
                        {{ __('tallcms::widgets.plugin_updates.manage') }}
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
