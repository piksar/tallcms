@props([
    'model' => null,
    'showFlag' => false,
    'showNative' => true,
    'style' => 'dropdown', // dropdown, links, or buttons
])
@php
    // Check if i18n is enabled
    if (!tallcms_i18n_enabled()) {
        return;
    }

    $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
    $currentLocale = app()->getLocale();
    $locales = $registry->getLocales();

    // Get alternate URLs for the model
    $alternateUrls = [];
    if ($model && method_exists($model, 'getTranslation')) {
        $alternateUrls = tallcms_alternate_urls($model);
    } else {
        // Fallback: use current page URL with locale prefix
        foreach ($locales as $code => $locale) {
            $alternateUrls[$code] = tallcms_localized_url(tallcms_current_slug(), $code);
        }
    }

    $currentLocaleData = $locales[$currentLocale] ?? null;
@endphp

@if(count($locales) > 1)
    @if($style === 'dropdown')
        <div x-data="{ open: false }" class="relative inline-block text-left" {{ $attributes }}>
            <button
                @click="open = !open"
                @click.outside="open = false"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                aria-expanded="false"
                aria-haspopup="true"
            >
                @if($showNative && $currentLocaleData)
                    <span>{{ $currentLocaleData['native'] }}</span>
                @else
                    <span>{{ strtoupper(\TallCms\Cms\Services\LocaleRegistry::toBcp47($currentLocale)) }}</span>
                @endif
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                role="menu"
                aria-orientation="vertical"
            >
                <div class="py-1" role="none">
                    @foreach($locales as $code => $locale)
                        @php $url = $alternateUrls[$code] ?? tallcms_localized_url(tallcms_current_slug(), $code); @endphp
                        <a
                            href="{{ url($url) }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm {{ $code === $currentLocale ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-700 hover:bg-gray-50' }}"
                            role="menuitem"
                            @if($code === $currentLocale) aria-current="true" @endif
                            hreflang="{{ \TallCms\Cms\Services\LocaleRegistry::toBcp47($code) }}"
                        >
                            @if($showNative)
                                <span>{{ $locale['native'] }}</span>
                            @else
                                <span>{{ $locale['label'] }}</span>
                            @endif
                            @if($code === $currentLocale)
                                <svg class="w-4 h-4 ml-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif($style === 'links')
        <nav class="flex items-center gap-2" {{ $attributes }} aria-label="{{ __('tallcms::frontend.language_selector') }}">
            @foreach($locales as $code => $locale)
                @php $url = $alternateUrls[$code] ?? tallcms_localized_url(tallcms_current_slug(), $code); @endphp
                <a
                    href="{{ url($url) }}"
                    class="px-2 py-1 text-sm {{ $code === $currentLocale ? 'font-bold text-primary-600' : 'text-gray-600 hover:text-gray-900' }}"
                    @if($code === $currentLocale) aria-current="true" @endif
                    hreflang="{{ \TallCms\Cms\Services\LocaleRegistry::toBcp47($code) }}"
                >
                    @if($showNative)
                        {{ $locale['native'] }}
                    @else
                        {{ strtoupper(\TallCms\Cms\Services\LocaleRegistry::toBcp47($code)) }}
                    @endif
                </a>
                @if(!$loop->last)
                    <span class="text-gray-300">|</span>
                @endif
            @endforeach
        </nav>

    @elseif($style === 'buttons')
        <div class="inline-flex rounded-md shadow-sm" {{ $attributes }} role="group" aria-label="{{ __('tallcms::frontend.language_selector') }}">
            @foreach($locales as $code => $locale)
                @php $url = $alternateUrls[$code] ?? tallcms_localized_url(tallcms_current_slug(), $code); @endphp
                <a
                    href="{{ url($url) }}"
                    class="px-4 py-2 text-sm font-medium border {{ $loop->first ? 'rounded-l-lg' : '' }} {{ $loop->last ? 'rounded-r-lg' : '' }} {{ $code === $currentLocale ? 'bg-primary-600 text-white border-primary-600 z-10' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }} {{ !$loop->first ? '-ml-px' : '' }}"
                    @if($code === $currentLocale) aria-current="true" @endif
                    hreflang="{{ \TallCms\Cms\Services\LocaleRegistry::toBcp47($code) }}"
                >
                    @if($showNative)
                        {{ $locale['native'] }}
                    @else
                        {{ strtoupper(\TallCms\Cms\Services\LocaleRegistry::toBcp47($code)) }}
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endif
