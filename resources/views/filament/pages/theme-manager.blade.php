<x-filament-panels::page>
    {{-- Theme Gallery Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @foreach($this->themes as $theme)
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border overflow-hidden transition-all duration-200 hover:shadow-md
                       {{ $theme['isActive'] ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-gray-700' }} h-full flex flex-col"
            >
                {{-- Screenshot (16:9 ratio - recommended: 1200×675px) --}}
                <div class="relative bg-gray-100 dark:bg-gray-900 shrink-0 overflow-hidden">
                    @if($theme['screenshot'])
                        <img
                            src="{{ $theme['screenshot'] }}"
                            alt="{{ $theme['name'] }} screenshot"
                            class="w-full aspect-video object-cover"
                        >
                    @else
                        <div class="aspect-video flex flex-col items-center justify-center text-gray-300 dark:text-gray-600">
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
                            <span class="text-xs text-gray-400 dark:text-gray-500 mt-1">No preview</span>
                        </div>
                    @endif

                    {{-- Active Badge --}}
                    @if($theme['isActive'])
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-500 text-white shadow-sm">
                                <x-heroicon-s-check-circle class="w-3 h-3 mr-0.5" />
                                Active
                            </span>
                        </div>
                    @endif

                    {{-- Status Badges --}}
                    @if(!$theme['meetsRequirements'])
                        <div class="absolute top-2 left-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-danger-500 text-white shadow-sm" title="Requirements not met">
                                <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                            </span>
                        </div>
                    @elseif(!$theme['isBuilt'] && $theme['isPrebuilt'])
                        <div class="absolute top-2 left-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-warning-500 text-white shadow-sm" title="Not built">
                                <x-heroicon-s-wrench class="w-3 h-3" />
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Theme Info --}}
                <div class="px-2.5 py-2 space-y-1.5 flex-1 flex flex-col">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight" title="{{ $theme['name'] }}">
                            {{ $theme['name'] }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate leading-tight">
                            v{{ $theme['version'] }} · {{ $theme['author'] }}
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 mt-auto">
                        @unless($theme['isActive'])
                            <x-filament::button
                                wire:click="activateTheme('{{ $theme['slug'] }}')"
                                size="xs"
                                :disabled="!$theme['meetsRequirements']"
                            >
                                Activate
                            </x-filament::button>

                            <x-filament::button
                                wire:click="previewTheme('{{ $theme['slug'] }}')"
                                color="gray"
                                size="xs"
                            >
                                Preview
                            </x-filament::button>
                        @else
                            <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">
                                Active
                            </span>
                        @endunless

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
        @endforeach
    </div>

    {{-- Empty State --}}
    @if($this->themes->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-paint-brush class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No themes found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add themes to the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">themes/</code> directory.
            </p>
        </div>
    @endif

    {{-- Theme Details Modal --}}
    <x-filament::modal id="theme-details-modal" width="2xl" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span>{{ $themeDetails['name'] ?? 'Theme Details' }}</span>
                @if($themeDetails)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        v{{ $themeDetails['version'] }}
                    </span>
                    @if($themeDetails['isActive'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                            <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                            Active
                        </span>
                    @endif
                @endif
            </div>
        </x-slot>

        @if($themeDetails)
            <div class="space-y-4 text-sm">
                {{-- Screenshot --}}
                @if($themeDetails['screenshot'])
                    <div class="aspect-video bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden shadow-sm">
                        <img
                            src="{{ $themeDetails['screenshot'] }}"
                            alt="{{ $themeDetails['name'] }}"
                            class="w-full h-full object-cover"
                        >
                    </div>
                @endif

                {{-- Description --}}
                <p class="text-gray-600 dark:text-gray-300">
                    {{ $themeDetails['description'] ?: 'No description available.' }}
                </p>

                {{-- Quick Actions --}}
                @unless($themeDetails['isActive'])
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="activateTheme('{{ $themeDetails['slug'] }}')"
                            :disabled="!$themeDetails['meetsRequirements']"
                            size="sm"
                        >
                            Activate Theme
                        </x-filament::button>
                        <x-filament::button
                            wire:click="previewTheme('{{ $themeDetails['slug'] }}')"
                            color="gray"
                            size="sm"
                        >
                            Preview
                        </x-filament::button>
                        <x-filament::button
                            wire:click="mountAction('delete', { slug: '{{ $themeDetails['slug'] }}', name: '{{ addslashes($themeDetails['name']) }}' })"
                            color="danger"
                            size="sm"
                            outlined
                        >
                            Delete
                        </x-filament::button>
                    </div>
                @endunless

                {{-- Requirements Warning --}}
                @if(!$themeDetails['meetsRequirements'])
                    <div class="p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg border border-danger-200 dark:border-danger-800">
                        <p class="font-medium text-danger-700 dark:text-danger-300">Requirements Not Met</p>
                        <ul class="text-danger-600 dark:text-danger-400 mt-1 space-y-0.5">
                            @foreach($themeDetails['unmetRequirements'] as $requirement)
                                <li>• {{ $requirement }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Color Palette --}}
                @if(!empty($themeDetails['tailwind']['colors']['primary']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
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

                {{-- Features Grid --}}
                @if(!empty($themeDetails['supports']))
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
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
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Information</h4>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Author</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if($themeDetails['authorUrl'])
                                    <a href="{{ $themeDetails['authorUrl'] }}" target="_blank" class="text-primary-600 hover:underline">
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
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
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
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate max-w-[70%]" title="{{ $themeDetails['path'] }}">
                        {{ $themeDetails['path'] }}
                    </p>
                    @if($themeDetails['homepage'])
                        <a
                            href="{{ $themeDetails['homepage'] }}"
                            target="_blank"
                            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 whitespace-nowrap"
                        >
                            Homepage →
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
