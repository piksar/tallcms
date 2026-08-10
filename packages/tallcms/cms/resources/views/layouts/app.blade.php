<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('theme', 'light') }}"
      x-data="{ theme: localStorage.getItem('theme') || 'light' }"
      x-init="$el.setAttribute('data-theme', theme)"
      @theme-changed.window="theme = $event.detail; localStorage.setItem('theme', theme); $el.setAttribute('data-theme', theme)">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO Meta Tags --}}
    <x-tallcms::seo.meta-tags
        :title="$title ?? null"
        :description="$description ?? null"
        :image="isset($featuredImage) && $featuredImage ? Storage::disk(cms_media_disk())->url($featuredImage) : null"
        :type="$seoType ?? 'website'"
        :article="$seoArticle ?? null"
        :twitter="$seoTwitter ?? null"
        :profile="$seoProfile ?? null"
    />

    {{-- Hreflang tags for multilingual SEO (only when i18n enabled) --}}
    @if(tallcms_i18n_enabled())
        <x-tallcms::hreflang :model="$seoPage ?? $seoPost ?? null" />
    @endif

    {{-- Structured Data --}}
    <x-tallcms::seo.structured-data
        :page="$seoPage ?? null"
        :post="$seoPost ?? null"
        :breadcrumbs="$seoBreadcrumbs ?? null"
        :includeWebsite="$seoIncludeWebsite ?? false"
    />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @php
        // Determine asset loading strategy
        // 1. Published package assets (plugin mode, no theme)
        // 2. Vite hot reload (development, standalone mode)
        // 3. Vite manifest (production, standalone mode)
        $usePublishedAssets = file_exists(public_path('vendor/tallcms/tallcms.css'));
        $useViteHot = file_exists(public_path('hot'));
        $useViteManifest = false;

        if (!$usePublishedAssets && !$useViteHot && file_exists(public_path('build/manifest.json'))) {
            // Check if manifest contains TallCMS entrypoints before using @vite
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $useViteManifest = isset($manifest['resources/css/app.css']) || isset($manifest['resources/js/app.js']);
        }
    @endphp

    @if($usePublishedAssets)
        <link rel="stylesheet" href="{{ asset('vendor/tallcms/tallcms.css') }}">
        <script src="{{ asset('vendor/tallcms/tallcms.js') }}" defer></script>
    @elseif($useViteHot || $useViteManifest)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
    </style>
    <x-tallcms::code-injection zone="head" />
</head>
<body class="font-inter antialiased bg-white">
    <x-tallcms::code-injection zone="body_start" />
    <div class="min-h-screen flex flex-col">
        @if(!($minimalChrome ?? false))
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="absolute top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
                <div class="flex justify-between h-20">

                    <!-- Logo -->
                    <div class="flex items-center">
                        @php
                            $logo = \TallCms\Cms\Models\SiteSetting::get('logo');
                            $siteName = \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name'));
                        @endphp
                        <a href="{{ tallcms_home_url() }}" class="flex items-center">
                            @if($logo)
                                <img src="{{ Storage::disk(cms_media_disk())->url($logo) }}"
                                     alt="{{ $siteName }}"
                                     class="h-10 w-auto">
                            @else
                                <span class="text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors duration-200">
                                    {{ $siteName }}
                                </span>
                            @endif
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-8">
                        <x-tallcms::menu location="header" style="horizontal" class="flex items-center space-x-8" />

                        {{-- Search --}}
                        @if(config('tallcms.search.enabled', true) && Route::has('tallcms.search'))
                            <form action="{{ route('tallcms.search') }}" method="GET" class="relative">
                                <input
                                    type="search"
                                    name="q"
                                    placeholder="{{ __('tallcms::frontend.search_placeholder') }}"
                                    class="w-40 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 focus:w-56"
                                    minlength="{{ config('tallcms.search.min_query_length', 2) }}"
                                />
                            </form>
                        @endif

                        {{-- Language Switcher (only when i18n enabled) --}}
                        @if(tallcms_i18n_enabled())
                            <x-tallcms::language-switcher :model="$seoPage ?? $seoPost ?? null" />
                        @endif
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button @click="open = !open" class="text-gray-700 hover:text-gray-900 p-2 transition-colors duration-200">
                            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 x-cloak
                 class="md:hidden bg-white/95 backdrop-blur-md shadow-lg border-t border-gray-100">
                <div class="px-4 py-4 space-y-3">
                    {{-- Mobile Search --}}
                    @if(config('tallcms.search.enabled', true) && Route::has('tallcms.search'))
                        <form action="{{ route('tallcms.search') }}" method="GET" class="mb-3">
                            <input
                                type="search"
                                name="q"
                                placeholder="{{ __('tallcms::frontend.search_placeholder') }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                minlength="{{ config('tallcms.search.min_query_length', 2) }}"
                            />
                        </form>
                    @endif

                    <x-tallcms::menu location="header" style="vertical" />

                    {{-- Language Switcher for Mobile (only when i18n enabled) --}}
                    @if(tallcms_i18n_enabled())
                        <div class="pt-3 border-t border-gray-100">
                            <x-tallcms::language-switcher :model="$seoPage ?? $seoPost ?? null" style="mobile" />
                        </div>
                    @endif
                </div>
            </div>
        </nav>
        @endif

        {{-- Breadcrumbs --}}
        @if($showBreadcrumbs ?? false)
            <x-tallcms::breadcrumbs :items="$breadcrumbItems ?? []" :over-hero="$breadcrumbsOverHero ?? false" />
        @endif

        <!-- Main Content -->
        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @if(!($minimalChrome ?? false))
        <!-- Footer -->
        <footer class="bg-gray-50">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-16">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand Section -->
                    <div class="col-span-1 md:col-span-2">
                        @php
                            $footerLogo = \TallCms\Cms\Models\SiteSetting::get('logo');
                            $footerSiteName = \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name'));
                        @endphp
                        @if($footerLogo)
                            <img src="{{ Storage::disk(cms_media_disk())->url($footerLogo) }}"
                                 alt="{{ $footerSiteName }}"
                                 class="h-10 w-auto mb-4">
                        @else
                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ $footerSiteName }}</h4>
                        @endif
                        <p class="text-gray-600 text-sm mb-6 max-w-md">
                            {{ \TallCms\Cms\Models\SiteSetting::get('site_tagline', 'A modern content management system built on the TALL stack.') }}
                        </p>
                        <div class="flex space-x-4">
                            <!-- Social Links -->
                            @php $fbUrl = \TallCms\Cms\Models\SiteSetting::get('social_facebook'); @endphp
                            @if($fbUrl)
                                <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">Facebook</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $twUrl = \TallCms\Cms\Models\SiteSetting::get('social_twitter'); @endphp
                            @if($twUrl)
                                <a href="{{ $twUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">Twitter/X</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $igUrl = \TallCms\Cms\Models\SiteSetting::get('social_instagram'); @endphp
                            @if($igUrl)
                                <a href="{{ $igUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">Instagram</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $liUrl = \TallCms\Cms\Models\SiteSetting::get('social_linkedin'); @endphp
                            @if($liUrl)
                                <a href="{{ $liUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">LinkedIn</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $ytUrl = \TallCms\Cms\Models\SiteSetting::get('social_youtube'); @endphp
                            @if($ytUrl)
                                <a href="{{ $ytUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">YouTube</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $ttUrl = \TallCms\Cms\Models\SiteSetting::get('social_tiktok'); @endphp
                            @if($ttUrl)
                                <a href="{{ $ttUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                    <span class="sr-only">TikTok</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                </a>
                            @endif
                            @php $nlUrl = \TallCms\Cms\Models\SiteSetting::get('newsletter_signup_url'); @endphp
                            @if($nlUrl)
                                <a href="{{ $nlUrl }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 transition-colors duration-200" title="Newsletter">
                                    <span class="sr-only">Newsletter</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-4">Quick Links</h5>
                        <x-tallcms::menu location="footer" style="footer-vertical" class="space-y-3 text-sm" />
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h5 class="font-semibold text-gray-900 mb-4">Contact</h5>
                        <div class="space-y-3 text-sm text-gray-600">
                            <p>{{ \TallCms\Cms\Models\SiteSetting::get('contact_email', config('tallcms.contact_email', 'hello@' . parse_url(config('app.url'), PHP_URL_HOST))) }}</p>
                            <x-tallcms::powered-by />
                        </div>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="mt-12 pt-8 border-t border-gray-200 text-center text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} {{ \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name')) }}. {{ __('tallcms::frontend.all_rights_reserved') }}</p>
                </div>
            </div>
        </footer>
        @endif
    </div>

    @livewireScripts

    {{-- Preserve theme across wire:navigate --}}
    <script>
        document.addEventListener('livewire:navigated', () => {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        });
    </script>
    <x-tallcms::code-injection zone="body_end" />
</body>
</html>
