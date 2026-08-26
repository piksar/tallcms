@props(['class' => '', 'model' => null])
@php
    // Check if i18n is enabled
    if (!tallcms_i18n_enabled()) {
        return;
    }

    $registry = app(\TallCms\Cms\Services\LocaleRegistry::class);
    $currentLocale = app()->getLocale();
    $locales = $registry->getLocales();

    // Need at least 2 locales to show switcher
    if (count($locales) < 2) {
        return;
    }

    // Get alternate URLs for the model or current page
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
    $currentLabel = $currentLocaleData['native'] ?? strtoupper(\TallCms\Cms\Services\LocaleRegistry::toBcp47($currentLocale));
@endphp

<!-- Language Switcher Dropdown -->
<div class="dropdown dropdown-end {{ $class }}">
    <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-1" aria-label="{{ __('tallcms::frontend.select_language') }}">
        <!-- Globe Icon -->
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>
        <span class="hidden sm:inline">{{ $currentLabel }}</span>
        <!-- Dropdown Arrow -->
        <svg class="w-3 h-3 hidden sm:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow-lg border border-base-300">
        @foreach($locales as $code => $locale)
            @php $url = $alternateUrls[$code] ?? tallcms_localized_url(tallcms_current_slug(), $code); @endphp
            <li>
                <a href="{{ url($url) }}"
                   class="{{ $code === $currentLocale ? 'active' : '' }}"
                   hreflang="{{ \TallCms\Cms\Services\LocaleRegistry::toBcp47($code) }}"
                   @if($code === $currentLocale) aria-current="true" @endif
                >
                    <span class="flex-1">{{ $locale['native'] }}</span>
                    @if($code !== $currentLocale && isset($locale['label']) && $locale['label'] !== $locale['native'])
                        <span class="text-xs opacity-60">{{ $locale['label'] }}</span>
                    @endif
                    @if($code === $currentLocale)
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</div>
