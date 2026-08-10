<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ daisyui_default_preset() }}" data-default-theme="{{ daisyui_default_preset() }}">
<head>
    <script>
        // Apply saved theme; reset localStorage when admin changes the default.
        // Namespaced under tallcms-* to avoid colliding with Filament's admin
        // light/dark toggle, which writes localStorage.theme on the same origin.
        (function() {
            var d = document.documentElement.getAttribute('data-default-theme');
            if (localStorage.getItem('tallcms-theme-default') !== d) {
                localStorage.removeItem('tallcms-theme');
                localStorage.setItem('tallcms-theme-default', d);
            }
            var s = localStorage.getItem('tallcms-theme');
            if (s) document.documentElement.setAttribute('data-theme', s);
        })();
    </script>
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

    {{-- Structured Data --}}
    <x-tallcms::seo.structured-data
        :page="$seoPage ?? null"
        :post="$seoPost ?? null"
        :breadcrumbs="$seoBreadcrumbs ?? null"
        :includeWebsite="$seoIncludeWebsite ?? false"
    />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    @if($favicon = \TallCms\Cms\Models\SiteSetting::get('favicon'))
        <link rel="icon" href="{{ Storage::disk(cms_media_disk())->url($favicon) }}">
    @endif

    <!-- Fonts: Plus Jakarta Sans (headings) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,600,700,800&family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- CMS Core Runtime (shared Alpine components) -->
    @tallcmsCoreJs
    <!-- Theme Assets -->
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
    <x-tallcms::code-injection zone="head" />
</head>
<body class="min-h-screen flex flex-col bg-base-100 text-base-content">
    <x-tallcms::code-injection zone="body_start" />

    {{-- Site-wide variables. Defined at layout scope so both the header
         (whichever branch renders) and the footer can use them without
         falling back to config('app.name'). --}}
    @php
        $logo = \TallCms\Cms\Models\SiteSetting::get('logo');
        $siteName = \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name'));
    @endphp

    <!-- Header -->
    @if(function_exists('mega_menu_header_active') && mega_menu_header_active('header'))
        {{-- Mega Menu Full Header --}}
        <x-dynamic-component component="mega-menu::header" location="header" />
    @elseif(function_exists('pro_header_active') && pro_header_active('header'))
        {{-- TallCMS Pro Full Header (Mode 2) - Legacy --}}
        <x-dynamic-component component="tallcms-pro::full-header" location="header" />
    @else
        {{-- Elevate Floating Nav --}}
        <div class="nav-shell sticky top-0 left-0 right-0 z-50">
            <div class="nav-bar navbar">
                <div class="navbar-start">
                    <!-- Mobile Menu -->
                    <div class="dropdown lg:hidden">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </div>
                        <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-lg bg-base-100/95 backdrop-blur-xl rounded-2xl w-56">
                            <x-menu location="header" style="vertical" />
                        </ul>
                    </div>
                    <!-- Logo -->
                    <a href="{{ tallcms_home_url() }}" class="flex items-center gap-2.5 px-2">
                        @if($logo)
                            <img src="{{ Storage::disk(cms_media_disk())->url($logo) }}" alt="{{ $siteName }}" class="h-8 w-auto">
                        @else
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-primary to-secondary"></div>
                            <span class="font-bold text-lg tracking-tight">{{ $siteName }}</span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="navbar-center hidden lg:flex">
                    <ul class="menu menu-horizontal gap-1 text-sm font-medium">
                        <x-menu location="header" style="horizontal" />
                    </ul>
                </div>

                <div class="navbar-end">
                    @if(tallcms_show_theme_switcher())
                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <ul tabindex="0" class="dropdown-content menu bg-base-100/95 backdrop-blur-xl rounded-2xl z-50 w-44 p-2 shadow-2xl border border-base-300/30">
                                @foreach(daisyui_presets() as $preset)
                                    <li>
                                        <input type="radio"
                                               name="theme-dropdown"
                                               class="theme-controller btn btn-sm btn-block btn-ghost justify-start"
                                               aria-label="{{ ucfirst($preset) }}"
                                               value="{{ $preset }}" />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Breadcrumbs --}}
    @if($showBreadcrumbs ?? false)
        <x-tallcms::breadcrumbs :items="$breadcrumbItems ?? []" />
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-neutral text-neutral-content">
        {{-- Pre-footer brand band --}}
        <div class="border-b border-neutral-content/10">
            <div class="max-w-5xl mx-auto px-6 py-12 text-center">
                <p class="text-lg text-neutral-content/60">{{ \TallCms\Cms\Models\SiteSetting::get('site_description', \TallCms\Cms\Models\SiteSetting::get('site_tagline', '')) }}</p>
            </div>
        </div>

        {{-- Main footer --}}
        <div class="max-w-7xl mx-auto px-6 py-12">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                <!-- Brand -->
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        @if($logo)
                            <img src="{{ Storage::disk(cms_media_disk())->url($logo) }}" alt="{{ $siteName }}" class="h-6 w-auto">
                        @else
                            <div class="w-6 h-6 rounded-md bg-gradient-to-br from-primary to-secondary"></div>
                        @endif
                        <span class="font-bold">{{ $siteName }}</span>
                    </div>
                    <p class="text-neutral-content/50 text-sm leading-relaxed mb-5">
                        {{ \TallCms\Cms\Models\SiteSetting::get('site_tagline', 'Building the future, one feature at a time.') }}
                    </p>
                    <!-- Social Icons -->
                    <div class="flex gap-2">
                        @if($twitterUrl = \TallCms\Cms\Models\SiteSetting::get('social_twitter'))
                            <a href="{{ $twitterUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-circle btn-xs text-neutral-content/50 hover:text-neutral-content" aria-label="Twitter">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        @endif
                        @if($linkedinUrl = \TallCms\Cms\Models\SiteSetting::get('social_linkedin'))
                            <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-circle btn-xs text-neutral-content/50 hover:text-neutral-content" aria-label="LinkedIn">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        @endif
                        @if($githubUrl = \TallCms\Cms\Models\SiteSetting::get('social_github'))
                            <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-circle btn-xs text-neutral-content/50 hover:text-neutral-content" aria-label="GitHub">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Footer Menu Column 1 -->
                <div>
                    <x-menu location="footer" style="footer-vertical" />
                </div>

                <!-- Footer Menu Column 2 -->
                <div>
                    <x-menu location="footer-2" style="footer-vertical" />
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-neutral-content/10">
            <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-content/40">
                <p>&copy; {{ date('Y') }} {{ \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name')) }}. {{ __('tallcms::frontend.all_rights_reserved') }}</p>
                <x-tallcms::powered-by />
            </div>
        </div>
    </footer>

    @livewireScripts
    <x-tallcms::code-injection zone="body_end" />
</body>
</html>
