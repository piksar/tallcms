<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

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
    <!-- Header -->
    @if(function_exists('mega_menu_header_active') && mega_menu_header_active('header'))
        {{-- Mega Menu Full Header --}}
        <x-dynamic-component component="mega-menu::header" location="header" />
    @elseif(function_exists('pro_header_active') && pro_header_active('header'))
        {{-- TallCMS Pro Full Header (Mode 2) - Legacy --}}
        <x-dynamic-component component="tallcms-pro::full-header" location="header" />
    @else
        {{-- Theme's Default Navbar --}}
        <div class="navbar bg-base-100 shadow-sm sticky top-0 z-50">
            <div class="navbar-start">
                <!-- Mobile Menu -->
                <div class="dropdown lg:hidden">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52">
                        <x-menu location="header" style="vertical" />
                    </ul>
                </div>
                <!-- Logo -->
                @php
                    $logo = \TallCms\Cms\Models\SiteSetting::get('logo');
                    $siteName = \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name'));
                @endphp
                <a href="{{ tallcms_home_url() }}" class="btn btn-ghost text-xl font-bold">
                    @if($logo)
                        <img src="{{ Storage::disk(cms_media_disk())->url($logo) }}" alt="{{ $siteName }}" class="h-8 w-auto">
                    @else
                        {{ $siteName }}
                    @endif
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    <x-menu location="header" style="horizontal" />
                </ul>
            </div>

            <div class="navbar-end gap-2">
                <!-- Theme Switcher -->
                {{-- Theme switcher: enable "all presets" mode to add theme-controller --}}
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
    <footer class="footer footer-center bg-base-200 text-base-content p-10">
        <aside>
            <p class="font-bold text-lg">{{ \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name')) }}</p>
            <p>Autumn theme for TallCMS</p>
        </aside>
        <nav>
            <x-menu location="footer" style="footer" />
        </nav>
        <aside>
            <p>&copy; {{ date('Y') }} {{ \TallCms\Cms\Models\SiteSetting::get('site_name', config('app.name')) }}. All rights reserved.</p>
            <x-tallcms::powered-by />
        </aside>
    </footer>

    @livewireScripts
    <x-tallcms::code-injection zone="body_end" />
</body>
</html>