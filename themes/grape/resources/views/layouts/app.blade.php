<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ $title ?? config('app.name') }}</title>
    
    @if(isset($description))
        <meta name="description" content="{{ $description }}">
    @endif
    
    @if(isset($featuredImage))
        <meta property="og:image" content="{{ Storage::url($featuredImage) }}">
        <meta property="twitter:image" content="{{ Storage::url($featuredImage) }}">
    @endif
    
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $description ?? '' }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Theme Assets -->
    @themeVite(['resources/css/app.css', 'resources/css/blocks.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-inter antialiased bg-white">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="absolute top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
                <div class="flex justify-between h-20">
                    
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-primary-600 hover:text-primary-700 transition-colors duration-200">
                            {{ config('app.name') }}
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-8">
                        <x-menu location="header" style="horizontal" class="flex items-center space-x-8" />
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
                    <x-menu location="header" style="vertical" />
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main>
            {{ $slot }}
        </main>
        
        <!-- Theme Footer -->
        <footer class="bg-gray-50">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-16">
                <div class="text-center">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">{{ config('app.name') }}</h4>
                    <p class="text-gray-600 text-sm mb-6">
                        Powered by TallCMS with Grape Theme
                    </p>
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <p class="text-sm text-gray-500">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    @livewireScripts
</body>
</html>