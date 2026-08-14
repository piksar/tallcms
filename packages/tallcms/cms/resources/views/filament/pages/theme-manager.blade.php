<x-filament-panels::page>
    {{-- Site switcher: super_admins see every site; site_owners see their own.
         Without this, a user with multiple managed sites has no way to pick
         which one they're configuring the theme for. --}}
    @php
        $manageableSites = $this->manageableSites();
        $currentContext = $this->getMultisiteContext();
    @endphp
    @if(count($manageableSites) > 1)
        <x-filament::section>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('tallcms::ui.managing_theme_for') }}
                        <span class="font-semibold">
                            {{ $currentContext?->name ?? __('tallcms::ui.select_a_site') }}
                            @if($currentContext)
                                <span class="text-gray-500 dark:text-gray-400 font-normal text-xs">
                                    ({{ $currentContext->domain }})
                                </span>
                            @endif
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('tallcms::ui.switch_sites_theme') }}
                    </p>
                </div>
                <div class="sm:w-80">
                    <select
                        wire:change="switchSite($event.target.value)"
                        class="block w-full rounded-lg border border-gray-200 bg-white text-sm text-gray-900 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="">{{ __('tallcms::ui.select_a_site_option') }}</option>
                        @foreach($manageableSites as $siteId => $label)
                            <option value="{{ $siteId }}" @selected($currentContext?->id === $siteId)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- From the Marketplace --}}
    @if($this->availableMarketplaceThemes->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500" />
                        {{ __('tallcms::ui.from_the_marketplace') }}
                    </div>
                    @if(config('tallcms.plugins.marketplace_url'))
                        <a href="{{ config('tallcms.plugins.marketplace_url') }}" target="_blank" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            {{ __('tallcms::ui.browse_marketplace') }}
                        </a>
                    @endif
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->availableMarketplaceThemes as $theme)
                    <x-filament::section class="!p-0 overflow-hidden relative">
                        @if($theme['featured'] ?? false)
                            <x-filament::badge color="primary" class="absolute top-2 right-2 z-10">
                                {{ __('tallcms::ui.featured') }}
                            </x-filament::badge>
                        @endif

                        @if($theme['screenshot_url'] ?? null)
                            <div class="bg-gray-100 dark:bg-white/5">
                                <img src="{{ $theme['screenshot_url'] }}" alt="{{ $theme['name'] }}" class="w-full aspect-video object-cover" loading="lazy" />
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-white/5 flex items-center justify-center py-6">
                                <x-heroicon-o-paint-brush class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                            </div>
                        @endif

                        <div class="p-3">
                            <h3 class="text-sm font-semibold truncate">{{ $theme['name'] }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ $theme['description'] }}
                            </p>

                            <div class="mt-3 flex items-center gap-1.5">
                                @if($theme['purchase_url'] ?? null)
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $theme['purchase_url'] }}"
                                        target="_blank"
                                        size="xs"
                                        icon="heroicon-o-shopping-cart"
                                    >
                                        {{ __('tallcms::ui.purchase') }}
                                    </x-filament::button>
                                @endif
                                @if($theme['download_url'] ?? null)
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $theme['download_url'] }}"
                                        target="_blank"
                                        color="{{ ($theme['purchase_url'] ?? null) ? 'gray' : 'primary' }}"
                                        size="xs"
                                        icon="heroicon-o-arrow-down-tray"
                                        :outlined="(bool) ($theme['purchase_url'] ?? null)"
                                    >
                                        {{ __('tallcms::ui.download') }}
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Search & Sort Bar --}}
    <div class="mb-4 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="{{ __('tallcms::ui.search_themes') }}"
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="sm:shrink-0">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="sort">
                        <option value="active">{{ __('tallcms::ui.sort_active_first') }}</option>
                        <option value="name">{{ __('tallcms::ui.sort_name_asc') }}</option>
                        <option value="preset">{{ __('tallcms::ui.sort_group_by_preset') }}</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Filter Chips --}}
        <div class="flex flex-wrap gap-2">
            @php
                $chips = [
                    ['prop' => 'filterDarkMode', 'label' => __('tallcms::ui.filter_dark_mode'), 'icon' => 'heroicon-o-moon', 'active' => $filterDarkMode],
                    ['prop' => 'filterThemeController', 'label' => __('tallcms::ui.filter_theme_controller'), 'icon' => 'heroicon-o-swatch', 'active' => $filterThemeController],
                    ['prop' => 'filterResponsive', 'label' => __('tallcms::ui.filter_responsive'), 'icon' => 'heroicon-o-device-phone-mobile', 'active' => $filterResponsive],
                    ['prop' => 'filterAnimations', 'label' => __('tallcms::ui.filter_animations'), 'icon' => 'heroicon-o-sparkles', 'active' => $filterAnimations],
                ];
            @endphp
            @foreach($chips as $chip)
                <button
                    wire:click="$toggle('{{ $chip['prop'] }}')"
                    type="button"
                    @class([
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border shadow-sm transition-colors cursor-pointer select-none',
                        'bg-primary-50 text-primary-700 border-primary-300 hover:bg-primary-100 dark:bg-primary-500/15 dark:text-primary-300 dark:border-primary-400/40 dark:hover:bg-primary-500/25' => $chip['active'],
                        'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-white/10 dark:hover:bg-gray-800' => !$chip['active'],
                    ])
                >
                    @if($chip['active'])
                        <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                    @else
                        <x-dynamic-component :component="$chip['icon']" class="w-3.5 h-3.5" />
                    @endif
                    {{ $chip['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Active Theme Summary Panel --}}
    @if($this->activeTheme)
        @php $active = $this->activeTheme; @endphp
        <x-filament::section compact class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                {{-- Top row: color strip + theme info --}}
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    {{-- Color strip mini --}}
                    @if(!empty($active['daisyuiColors']))
                        <div class="flex h-6 w-24 rounded overflow-hidden shrink-0">
                            @foreach($active['daisyuiColors'] as $color)
                                <div class="grow shrink basis-0" style="background: {{ $color }};"></div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Theme info --}}
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="font-semibold text-gray-950 dark:text-white truncate">{{ $active['name'] }}</span>
                        <x-filament::badge color="success" size="sm">{{ __('tallcms::ui.status_active') }}</x-filament::badge>
                        <span class="text-xs text-gray-400 dark:text-gray-500">v{{ $active['version'] }}</span>
                        @if($active['daisyuiPreset'])
                            <x-filament::badge color="info" size="sm">{{ ucfirst($active['daisyuiPreset']) }}</x-filament::badge>
                        @endif
                    </div>

                    {{-- Feature indicators --}}
                    <div class="hidden lg:flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 shrink-0">
                        @if($active['hasDarkMode'])
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-moon class="w-3.5 h-3.5" />
                                {{ __('tallcms::ui.filter_dark_mode') }}
                            </span>
                        @endif
                        @if($active['hasThemeController'])
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-swatch class="w-3.5 h-3.5" />
                                {{ __('tallcms::ui.filter_theme_controller') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Bottom row on mobile: controls --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Default Preset Selector --}}
                    @if($active['hasThemeController'] && !empty($active['presets']))
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('tallcms::ui.default_preset') }}</span>
                            <x-filament::input.wrapper class="!w-auto">
                                <x-filament::input.select
                                    wire:change="changeDefaultPreset($event.target.value)"
                                >
                                    @foreach($active['presets'] as $preset)
                                        <option value="{{ $preset }}" @selected($preset === $active['defaultPreset'])>
                                            {{ ucfirst($preset) }}
                                        </option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                    @endif

                    {{-- Quick links --}}
                    <x-filament::button
                        tag="a"
                        href="{{ url('/') }}"
                        target="_blank"
                        color="gray"
                        size="xs"
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        {{ __('tallcms::ui.visit_site') }}
                    </x-filament::button>
                    <x-filament::button
                        wire:click="previewTheme('{{ $active['slug'] }}')"
                        color="gray"
                        size="xs"
                        outlined
                    >
                        {{ __('tallcms::ui.preview') }}
                    </x-filament::button>

                    {{-- Rollback info --}}
                    @if($this->canRollback())
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Rollback: {{ $this->getRollbackSlug() }}
                        </span>
                    @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    {{-- Theme Gallery Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->paginatedThemes as $theme)
            <x-filament::section
                :class="$theme['isActive'] ? 'ring-3 ring-primary-500 bg-primary-50/50 dark:bg-primary-900/10' : ''"
                class="!p-0 overflow-hidden h-full"
            >
                <div class="flex flex-col h-full">
                {{-- Screenshot (16:9 ratio - recommended: 1200x675px) --}}
                <div class="relative bg-gray-100 dark:bg-white/5 shrink-0 overflow-hidden">
                    @if($theme['screenshot'])
                        <img
                            src="{{ $theme['screenshot'] }}"
                            alt="{{ $theme['name'] }} screenshot"
                            class="w-full aspect-video object-cover"
                        >
                    @elseif(!empty($theme['daisyuiColors']))
                        {{-- Color palette placeholder --}}
                        <div class="aspect-video relative overflow-hidden">
                            @php
                                $colors = array_values($theme['daisyuiColors']);
                                $base = $colors[4] ?? $colors[0];
                                $primary = $colors[0] ?? '#6366f1';
                                $secondary = $colors[1] ?? '#a855f7';
                                $accent = $colors[2] ?? '#06b6d4';
                                $neutral = $colors[3] ?? '#374151';
                            @endphp
                            <div class="absolute inset-0" style="background: {{ $base }};"></div>
                            <div class="absolute inset-0 flex items-end">
                                {{-- Decorative blocks mimicking a UI layout --}}
                                <div class="w-full p-3 space-y-2">
                                    <div class="h-3 w-2/3 rounded-sm opacity-90" style="background: {{ $primary }};"></div>
                                    <div class="h-2 w-5/6 rounded-sm opacity-50" style="background: {{ $neutral }};"></div>
                                    <div class="h-2 w-1/2 rounded-sm opacity-50" style="background: {{ $neutral }};"></div>
                                    <div class="flex gap-2 pt-1">
                                        <div class="h-5 w-16 rounded-sm opacity-90" style="background: {{ $primary }};"></div>
                                        <div class="h-5 w-16 rounded-sm opacity-70" style="background: {{ $secondary }};"></div>
                                        <div class="h-5 w-10 rounded-sm opacity-60" style="background: {{ $accent }};"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="absolute bottom-1.5 right-2 text-[10px] opacity-40" style="color: {{ $neutral }};">{{ ucfirst($theme['daisyuiPreset'] ?? 'theme') }}</span>
                        </div>
                    @else
                        <div class="aspect-video flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                            <svg
                                viewBox="0 0 320 180"
                                class="w-16 h-12"
                                role="img"
                                aria-label="No preview available"
                            >
                                <rect x="8" y="8" width="304" height="164" rx="12" fill="currentColor" fill-opacity="0.3" />
                                <path
                                    d="M52 130l44-48 30 34 42-54 60 74"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle cx="226" cy="64" r="14" fill="none" stroke="currentColor" stroke-width="10" />
                            </svg>
                            <span class="text-xs mt-1">No preview</span>
                        </div>
                    @endif

                    {{-- Active Badge --}}
                    @if($theme['isActive'])
                        @php
                            $hasConfigurableDisplayOptions = $theme['hasThemeController']
                                || ($theme['supports']['search'] ?? false)
                                || ($theme['supports']['language_switcher'] ?? false);
                        @endphp
                        <div class="absolute top-2 right-2">
                            <x-filament::badge
                                color="success"
                                size="sm"
                                :tooltip="$hasConfigurableDisplayOptions ? 'Open Details to toggle header controls' : null"
                            >
                                <x-heroicon-s-check-circle class="w-3 h-3 mr-0.5" />
                                Active
                                @if($hasConfigurableDisplayOptions)
                                    <x-heroicon-m-adjustments-horizontal class="w-3 h-3 ml-1 opacity-80" />
                                @endif
                            </x-filament::badge>
                        </div>
                    @endif

                    {{-- Status Badges --}}
                    @if(!$theme['meetsRequirements'])
                        <div class="absolute top-2 left-2">
                            <x-filament::badge color="danger" size="sm" title="Requirements not met">
                                <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                            </x-filament::badge>
                        </div>
                    @elseif(!$theme['isBuilt'] && $theme['isPrebuilt'])
                        <div class="absolute top-2 left-2">
                            <x-filament::badge color="warning" size="sm" title="Not built">
                                <x-heroicon-s-wrench class="w-3 h-3" />
                            </x-filament::badge>
                        </div>
                    @endif
                </div>

                {{-- DaisyUI Color Strip --}}
                @if(!empty($theme['daisyuiColors']))
                    <div class="flex h-1.5">
                        @foreach($theme['daisyuiColors'] as $colorName => $color)
                            <div class="grow shrink basis-0" style="background: {{ $color }};"></div>
                        @endforeach
                    </div>
                @endif

                {{-- Theme Info --}}
                <div class="px-3 py-2.5 space-y-2 flex-1 flex flex-col">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white truncate leading-tight" title="{{ $theme['name'] }}">
                                {{ $theme['name'] }}
                            </h3>
                            <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">v{{ $theme['version'] }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">
                            {{ $theme['author'] }}
                        </p>
                    </div>

                    {{-- Description --}}
                    @if($theme['description'])
                        <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">
                            {{ $theme['description'] }}
                        </p>
                    @endif

                    {{-- Feature Badges --}}
                    <div class="flex flex-wrap gap-1">
                        @if($theme['hasDarkMode'])
                            <x-filament::badge color="gray" size="sm">{{ __('tallcms::ui.filter_dark_mode') }}</x-filament::badge>
                        @endif
                        @if($theme['hasThemeController'])
                            <x-filament::badge color="gray" size="sm">{{ __('tallcms::ui.filter_theme_controller') }}</x-filament::badge>
                        @endif
                        @if($theme['hasResponsive'])
                            <x-filament::badge color="gray" size="sm">Responsive</x-filament::badge>
                        @endif
                        @if($theme['hasAnimations'])
                            <x-filament::badge color="gray" size="sm">Animations</x-filament::badge>
                        @endif
                        @if($theme['daisyuiPreset'])
                            <x-filament::badge color="info" size="sm">{{ ucfirst($theme['daisyuiPreset']) }}</x-filament::badge>
                        @endif
                        @if($theme['requiresLicense'])
                            @if($theme['licenseStatus']['is_valid'] ?? false)
                                <x-filament::badge :color="$theme['licenseStatus']['status_color']" size="sm">
                                    {{ $theme['licenseStatus']['status_label'] }}
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning" size="sm">Premium</x-filament::badge>
                            @endif
                        @endif
                    </div>

                    {{-- Tag Badges --}}
                    @if(!empty($theme['tags']))
                        <div class="flex flex-wrap gap-1">
                            @foreach($theme['tags'] as $tag)
                                <x-filament::badge color="primary" size="sm" class="!bg-primary-50 !text-primary-600 !ring-primary-200 dark:!bg-primary-500/10 dark:!text-primary-400 dark:!ring-primary-500/20">
                                    {{ ucfirst($tag) }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    @endif

                    {{-- Readiness Status --}}
                    <div class="text-xs">
                        @if($theme['isActive'])
                            <span class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400">
                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                {{ __('tallcms::ui.active_theme') }}
                            </span>
                        @elseif($theme['requiresLicense'] && !($theme['licenseStatus']['is_valid'] ?? false))
                            <span class="inline-flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
                                <x-heroicon-s-key class="w-3.5 h-3.5" />
                                License required
                            </span>
                        @elseif(!$theme['meetsRequirements'])
                            <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
                                <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5" />
                                Missing requirements
                            </span>
                        @elseif(!$theme['isBuilt'] && $theme['isPrebuilt'])
                            <span class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400">
                                <x-heroicon-s-wrench class="w-3.5 h-3.5" />
                                Needs build
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                Ready to activate
                            </span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-1.5 mt-auto pt-1">
                        @unless($theme['isActive'])
                            <x-filament::button
                                wire:click="previewTheme('{{ $theme['slug'] }}')"
                                size="xs"
                            >
                                {{ __('tallcms::ui.preview') }}
                            </x-filament::button>

                            <x-filament::button
                                wire:click="activateTheme('{{ $theme['slug'] }}')"
                                color="gray"
                                size="xs"
                                outlined
                                :disabled="!$theme['meetsRequirements']"
                            >
                                Activate
                            </x-filament::button>
                        @else
                            <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">
                                Active
                            </span>
                        @endunless

                        {{-- License Actions --}}
                        @if($theme['requiresLicense'])
                            @if($theme['licenseStatus']['has_license'] ?? false)
                                @if($theme['licenseStatus']['is_valid'] ?? false)
                                    <x-filament::button
                                        wire:click="refreshLicenseStatus('{{ $theme['licenseSlug'] }}')"
                                        color="gray"
                                        size="xs"
                                        outlined
                                        icon="heroicon-o-arrow-path"
                                    >
                                        Refresh
                                    </x-filament::button>
                                    <x-filament::button
                                        wire:click="mountAction('deactivateLicense', { licenseSlug: '{{ $theme['licenseSlug'] }}', name: '{{ addslashes($theme['name']) }}' })"
                                        color="danger"
                                        size="xs"
                                        outlined
                                        icon="heroicon-o-x-circle"
                                    >
                                        Deactivate
                                    </x-filament::button>
                                @else
                                    <x-filament::button
                                        wire:click="mountAction('activateLicense', { licenseSlug: '{{ $theme['licenseSlug'] }}', name: '{{ addslashes($theme['name']) }}' })"
                                        color="primary"
                                        size="xs"
                                        icon="heroicon-o-key"
                                    >
                                        Activate
                                    </x-filament::button>
                                @endif
                            @else
                                <x-filament::button
                                    wire:click="mountAction('activateLicense', { licenseSlug: '{{ $theme['licenseSlug'] }}', name: '{{ addslashes($theme['name']) }}' })"
                                    color="primary"
                                    size="xs"
                                    icon="heroicon-o-key"
                                >
                                    Activate
                                </x-filament::button>
                                @if($theme['purchaseUrl'] ?? ($theme['licenseStatus']['purchase_url'] ?? null))
                                    <x-filament::button
                                        tag="a"
                                        href="{{ $theme['purchaseUrl'] ?? $theme['licenseStatus']['purchase_url'] }}"
                                        target="_blank"
                                        color="gray"
                                        size="xs"
                                        outlined
                                        icon="heroicon-o-shopping-cart"
                                    >
                                        {{ __('tallcms::ui.purchase') }}
                                    </x-filament::button>
                                @endif
                            @endif
                        @endif

                        <x-filament::button
                            wire:click="showThemeDetails('{{ $theme['slug'] }}')"
                            color="gray"
                            size="xs"
                            outlined
                            class="ml-auto"
                        >
                            Details
                        </x-filament::button>
                    </div>
                </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- Theme Pagination --}}
    @if($this->themePageCount > 1)
        <div class="flex items-center justify-between mt-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing {{ ($this->themePage - 1) * $this->themesPerPage + 1 }}–{{ min($this->themePage * $this->themesPerPage, $this->filteredThemes->count()) }} of {{ $this->filteredThemes->count() }} themes
            </p>
            <div class="flex items-center gap-1">
                <x-filament::button
                    wire:click="goToThemePage({{ $this->themePage - 1 }})"
                    color="gray"
                    size="sm"
                    outlined
                    :disabled="$this->themePage <= 1"
                >
                    Previous
                </x-filament::button>
                @for($i = 1; $i <= $this->themePageCount; $i++)
                    <x-filament::button
                        wire:click="goToThemePage({{ $i }})"
                        :color="$i === $this->themePage ? 'primary' : 'gray'"
                        size="sm"
                        :outlined="$i !== $this->themePage"
                    >
                        {{ $i }}
                    </x-filament::button>
                @endfor
                <x-filament::button
                    wire:click="goToThemePage({{ $this->themePage + 1 }})"
                    color="gray"
                    size="sm"
                    outlined
                    :disabled="$this->themePage >= $this->themePageCount"
                >
                    Next
                </x-filament::button>
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if($this->filteredThemes->isEmpty())
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3 mb-4">
                <x-heroicon-o-paint-brush class="w-6 h-6 text-gray-400 dark:text-gray-500" />
            </div>
            @if($this->themes->isEmpty())
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('tallcms::ui.no_themes_found') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('tallcms::ui.no_themes_found_hint', ['command' => 'php artisan make:theme']) }}
                </p>
            @else
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('tallcms::ui.no_themes_match_search') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('tallcms::ui.try_adjusting_search_or_filters') }}</p>
                <div class="flex gap-2 mt-3">
                    @if($search)
                        <x-filament::button
                            wire:click="$set('search', '')"
                            color="gray"
                            size="sm"
                        >
                            {{ __('tallcms::ui.clear_search') }}
                        </x-filament::button>
                    @endif
                    @if($filterDarkMode || $filterThemeController || $filterResponsive || $filterAnimations)
                        <x-filament::button
                            wire:click="$set('filterDarkMode', false); $set('filterThemeController', false); $set('filterResponsive', false); $set('filterAnimations', false)"
                            color="gray"
                            size="sm"
                        >
                            {{ __('tallcms::ui.clear_filters') }}
                        </x-filament::button>
                    @endif
                    @if($search || $filterDarkMode || $filterThemeController || $filterResponsive || $filterAnimations || $sort !== 'active')
                        <x-filament::button
                            wire:click="$set('search', ''); $set('filterDarkMode', false); $set('filterThemeController', false); $set('filterResponsive', false); $set('filterAnimations', false); $set('sort', 'active')"
                            color="primary"
                            size="sm"
                            outlined
                        >
                            {{ __('tallcms::ui.reset_all') }}
                        </x-filament::button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Theme Details Modal --}}
    <x-filament::modal id="theme-details-modal" width="2xl" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span>{{ $themeDetails['name'] ?? 'Theme Details' }}</span>
                @if($themeDetails)
                    <x-filament::badge color="gray" size="sm">
                        v{{ $themeDetails['version'] }}
                    </x-filament::badge>
                    @if($themeDetails['isActive'])
                        <x-filament::badge color="success" size="sm">
                            <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                            {{ __('tallcms::ui.status_active') }}
                        </x-filament::badge>
                    @endif
                @endif
            </div>
        </x-slot>

        @if($themeDetails)
            <div class="space-y-4 text-sm">
                {{-- Screenshot --}}
                @if($themeDetails['screenshot'])
                    <div
                        x-data="{
                            gallery: @js($themeDetails['gallery'] ?? []),
                            mainImage: @js($themeDetails['screenshot']),
                            originalImage: @js($themeDetails['screenshot']),
                        }"
                    >
                        <div class="aspect-video bg-gray-100 dark:bg-white/5 rounded-lg overflow-hidden shadow-sm">
                            <img
                                :src="mainImage"
                                alt="{{ $themeDetails['name'] }}"
                                class="w-full h-full object-cover"
                            >
                        </div>
                        {{-- Gallery Thumbnails --}}
                        <template x-if="gallery.length > 0">
                            <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                                <button
                                    @click="mainImage = originalImage"
                                    class="shrink-0 w-16 h-10 rounded overflow-hidden ring-2 transition-all"
                                    :class="mainImage === originalImage ? 'ring-primary-500' : 'ring-transparent hover:ring-gray-300 dark:hover:ring-gray-600'"
                                >
                                    <img :src="originalImage" alt="Primary" class="w-full h-full object-cover">
                                </button>
                                <template x-for="(img, idx) in gallery" :key="idx">
                                    <button
                                        @click="mainImage = img"
                                        class="shrink-0 w-16 h-10 rounded overflow-hidden ring-2 transition-all"
                                        :class="mainImage === img ? 'ring-primary-500' : 'ring-transparent hover:ring-gray-300 dark:hover:ring-gray-600'"
                                    >
                                        <img :src="img" :alt="'Gallery ' + (idx + 1)" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                @endif

                {{-- At a Glance --}}
                <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">At a Glance</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-tag class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Version</div>
                                <div class="text-gray-900 dark:text-white font-medium">{{ $themeDetails['version'] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Author</div>
                                <div class="text-gray-900 dark:text-white font-medium truncate">
                                    @if($themeDetails['authorUrl'])
                                        <a href="{{ $themeDetails['authorUrl'] }}" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">{{ $themeDetails['author'] }}</a>
                                    @else
                                        {{ $themeDetails['author'] }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-swatch class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Preset</div>
                                <div class="text-gray-900 dark:text-white font-medium">{{ ucfirst($themeDetails['daisyui']['preset'] ?? 'N/A') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-moon class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Dark Mode</div>
                                <div class="text-gray-900 dark:text-white font-medium">{{ ($themeDetails['hasDarkMode'] ?? false) ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-device-phone-mobile class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Responsive</div>
                                <div class="text-gray-900 dark:text-white font-medium">{{ ($themeDetails['hasResponsive'] ?? false) ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-wrench class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 uppercase">Built</div>
                                <div class="text-gray-900 dark:text-white font-medium">{{ $themeDetails['isBuilt'] ? 'Yes' : 'No' }}</div>
                            </div>
                        </div>
                    </div>
                    {{-- Tags --}}
                    @if(!empty($themeDetails['tags']))
                        <div class="flex flex-wrap gap-1 mt-3 pt-3 border-t border-gray-200 dark:border-white/10">
                            @foreach($themeDetails['tags'] as $tag)
                                <x-filament::badge color="primary" size="sm" class="!bg-primary-50 !text-primary-600 !ring-primary-200 dark:!bg-primary-500/10 dark:!text-primary-400 dark:!ring-primary-500/20">
                                    {{ ucfirst($tag) }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Description --}}
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $themeDetails['description'] ?: 'No description available.' }}
                </p>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap gap-2">
                    @unless($themeDetails['isActive'])
                        <x-filament::button
                            wire:click="previewTheme('{{ $themeDetails['slug'] }}')"
                            size="sm"
                        >
                            {{ __('tallcms::ui.preview') }}
                        </x-filament::button>
                        <x-filament::button
                            wire:click="activateTheme('{{ $themeDetails['slug'] }}')"
                            color="gray"
                            size="sm"
                            outlined
                            :disabled="!$themeDetails['meetsRequirements']"
                        >
                            Activate Theme
                        </x-filament::button>
                        <x-filament::button
                            wire:click="mountAction('delete', { slug: '{{ $themeDetails['slug'] }}', name: '{{ addslashes($themeDetails['name']) }}' })"
                            color="danger"
                            size="sm"
                            outlined
                        >
                            Delete
                        </x-filament::button>
                    @endunless
                    @if($themeDetails['requiresLicense'] ?? false)
                        @if(($themeDetails['licenseStatus']['has_license'] ?? false) && ($themeDetails['licenseStatus']['is_valid'] ?? false))
                            <x-filament::button
                                wire:click="refreshLicenseStatus('{{ $themeDetails['licenseSlug'] }}')"
                                color="gray"
                                size="sm"
                                outlined
                                icon="heroicon-o-arrow-path"
                            >
                                Refresh License
                            </x-filament::button>
                            <x-filament::button
                                wire:click="mountAction('deactivateLicense', { licenseSlug: '{{ $themeDetails['licenseSlug'] }}', name: '{{ addslashes($themeDetails['name']) }}' })"
                                color="danger"
                                size="sm"
                                outlined
                                icon="heroicon-o-x-circle"
                            >
                                Deactivate
                            </x-filament::button>
                        @else
                            <x-filament::button
                                wire:click="mountAction('activateLicense', { licenseSlug: '{{ $themeDetails['licenseSlug'] }}', name: '{{ addslashes($themeDetails['name']) }}' })"
                                color="primary"
                                size="sm"
                                icon="heroicon-o-key"
                            >
                                Activate License
                            </x-filament::button>
                        @endif
                    @endif
                </div>

                {{-- Requirements Warning --}}
                @if(!$themeDetails['meetsRequirements'])
                    <div class="p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg border border-danger-200 dark:border-danger-800">
                        <p class="font-medium text-danger-700 dark:text-danger-300">Requirements Not Met</p>
                        <ul class="text-danger-600 dark:text-danger-400 mt-1 space-y-0.5">
                            @foreach($themeDetails['unmetRequirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- License Information --}}
                @if($themeDetails['licenseStatus']['has_license'] ?? false)
                    <x-filament::section compact>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                License
                                <x-filament::badge :color="$themeDetails['licenseStatus']['status_color']" size="sm">
                                    {{ $themeDetails['licenseStatus']['status_label'] }}
                                </x-filament::badge>
                            </div>
                        </x-slot>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">License Key</dt>
                                <dd class="font-mono text-gray-900 dark:text-white">{{ $themeDetails['licenseStatus']['license_key'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Domain</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $themeDetails['licenseStatus']['domain'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Activated</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $themeDetails['licenseStatus']['activated_at'] ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Expires</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $themeDetails['licenseStatus']['expires_at'] ?? 'Never' }}</dd>
                            </div>
                        </dl>
                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            Last validated: {{ $themeDetails['licenseStatus']['last_validated'] ?? 'Never' }}
                        </p>
                    </x-filament::section>
                @elseif($themeDetails['requiresLicense'] ?? false)
                    <x-filament::section compact class="!bg-warning-50 dark:!bg-warning-950 !border-warning-200 dark:!border-warning-800">
                        <x-slot name="heading">
                            <span class="text-warning-700 dark:text-warning-300">License Required</span>
                        </x-slot>
                        <p class="text-sm text-warning-600 dark:text-warning-400">
                            {{ __('tallcms::ui.theme_requires_license') }}
                        </p>
                    </x-filament::section>
                @endif

                {{-- Color Palette (Tailwind) --}}
                @if(!empty($themeDetails['tailwind']['colors']['primary']))
                    <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Primary Colors</h4>
                        <div class="flex rounded-sm overflow-hidden h-8">
                            @foreach($themeDetails['tailwind']['colors']['primary'] as $shade => $color)
                                <div
                                    class="grow shrink basis-0"
                                    style="background: {!! $color !!};"
                                    title="{{ $shade }}: {{ $color }}"
                                >&nbsp;</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- DaisyUI Preset Info --}}
                @if(!empty($themeDetails['daisyui']['preset']))
                    <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">DaisyUI Theme</h4>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <x-filament::badge color="primary" size="sm">
                                {{ ucfirst($themeDetails['daisyui']['preset']) }}
                            </x-filament::badge>
                            @if(!empty($themeDetails['daisyui']['custom']))
                                <x-filament::badge color="warning" size="sm">
                                    Custom
                                </x-filament::badge>
                            @endif
                            @if(!empty($themeDetails['daisyui']['prefersDark']))
                                <x-filament::badge color="gray" size="sm">
                                    Dark: {{ ucfirst($themeDetails['daisyui']['prefersDark']) }}
                                </x-filament::badge>
                            @endif
                            @if(!empty($themeDetails['daisyui']['presets']) && $themeDetails['daisyui']['presets'] === 'all')
                                <x-filament::badge color="info" size="sm">
                                    All Presets
                                </x-filament::badge>
                            @elseif(!empty($themeDetails['daisyui']['presets']) && is_array($themeDetails['daisyui']['presets']))
                                <x-filament::badge color="gray" size="sm">
                                    {{ count($themeDetails['daisyui']['presets']) }} presets
                                </x-filament::badge>
                            @endif
                        </div>
                        {{-- Custom Theme Color Palette --}}
                        @if(!empty($themeDetails['daisyui']['colors']))
                            <div class="flex rounded-sm overflow-hidden h-6">
                                @foreach($themeDetails['daisyui']['colors'] as $name => $color)
                                    <div
                                        class="grow shrink basis-0"
                                        style="background: {{ $color }};"
                                        title="{{ $name }}: {{ $color }}"
                                    >&nbsp;</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Display Options (active theme only) --}}
                @php
                    $hasSearchSupport = (bool) ($themeDetails['supports']['search'] ?? false);
                    $hasLangSupport = (bool) ($themeDetails['supports']['language_switcher'] ?? false);
                    $hasAnyDisplayOption = $themeDetails['hasThemeController'] || $hasSearchSupport || $hasLangSupport;
                @endphp
                @if($themeDetails['isActive'] && $hasAnyDisplayOption)
                    <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Display Options</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Show or hide controls in the site header. Saved automatically.</p>
                        <div class="space-y-2">
                            @if($themeDetails['hasThemeController'])
                                <label class="flex items-center justify-between gap-3 cursor-pointer">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Theme switcher</span>
                                    <input
                                        type="checkbox"
                                        class="toggle toggle-sm toggle-primary"
                                        wire:model.live="showThemeSwitcher"
                                    />
                                </label>
                            @endif
                            @if($hasSearchSupport)
                                <label class="flex items-center justify-between gap-3 cursor-pointer">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Search box</span>
                                    <input
                                        type="checkbox"
                                        class="toggle toggle-sm toggle-primary"
                                        wire:model.live="showSearch"
                                    />
                                </label>
                            @endif
                            @if($hasLangSupport)
                                <label class="flex items-center justify-between gap-3 cursor-pointer">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Language dropdown</span>
                                    <input
                                        type="checkbox"
                                        class="toggle toggle-sm toggle-primary"
                                        wire:model.live="showLanguageDropdown"
                                    />
                                </label>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Features Grid --}}
                @if(!empty($themeDetails['supports']))
                    <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Features</h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                            @foreach($themeDetails['supports'] as $feature => $enabled)
                                <div class="flex items-center gap-1.5">
                                    @if($enabled === true)
                                        <svg style="width: 14px; height: 14px; min-width: 14px;" class="text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                                    @else
                                        <svg style="width: 14px; height: 14px; min-width: 14px;" class="text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-400 dark:text-gray-500">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Theme Information --}}
                <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Information</h4>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Author</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if($themeDetails['authorUrl'])
                                    <a href="{{ $themeDetails['authorUrl'] }}" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">
                                        {{ $themeDetails['author'] }}
                                    </a>
                                @else
                                    {{ $themeDetails['author'] }}
                                @endif
                            </dd>
                        </div>
                        @if($themeDetails['license'])
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">License</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $themeDetails['license'] }}</dd>
                            </div>
                        @endif
                        @if($themeDetails['parent'])
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Parent Theme</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $themeDetails['parent'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Compatibility --}}
                @if(!empty($themeDetails['compatibility']))
                    <div class="bg-gray-50 dark:bg-white/5 rounded-lg p-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Compatibility</h4>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            @if(!empty($themeDetails['compatibility']['php']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">PHP</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $themeDetails['compatibility']['php'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($themeDetails['compatibility']['tallcms']))
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">TallCMS</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $themeDetails['compatibility']['tallcms'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($themeDetails['compatibility']['extensions']))
                                <div class="col-span-2">
                                    <dt class="text-gray-500 dark:text-gray-400">Extensions</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ implode(', ', $themeDetails['compatibility']['extensions']) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="pt-3 border-t border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate max-w-[70%]" title="{{ $themeDetails['path'] }}">
                        {{ $themeDetails['path'] }}
                    </p>
                    @if($themeDetails['homepage'])
                        <a
                            href="{{ $themeDetails['homepage'] }}"
                            target="_blank"
                            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 whitespace-nowrap"
                        >
                            Homepage &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::modal>

    {{-- JavaScript for preview --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-preview', ({ url }) => {
                window.open(url, '_blank');
            });
        });
    </script>
</x-filament-panels::page>
