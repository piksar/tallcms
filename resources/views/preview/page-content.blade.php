{{-- Full page layout rendering - using modern fluid design system --}}
<div class="font-inter antialiased bg-white">
    <div class="min-h-screen">
        <!-- Modern Glassmorphism Navigation -->
        <nav x-data="{ open: false }" class="absolute top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
                <div class="flex justify-between h-20">
                    
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900"
                           style="font-size: clamp(1.25rem, 2.5vw, 1.5rem); font-weight: bold; color: #111827;">
                            {{ config('app.name') }}
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <x-menu location="header" style="horizontal" class="preview-desktop-menu hidden md:flex items-center space-x-8" />

                    <!-- Mobile Menu Button -->
                    <div class="preview-mobile-menu-btn md:hidden flex items-center">
                        <button @click="open = !open" class="text-gray-700 p-2 hover:bg-gray-100 rounded-lg transition-colors">
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

            <!-- Mobile Menu with Backdrop -->
            <div x-show="open" x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-2"
                 class="preview-mobile-menu absolute top-full left-0 right-0 bg-white/95 backdrop-blur-md shadow-lg md:hidden">
                <div class="px-4 sm:px-6 py-6 space-y-4">
                    <x-menu location="header" style="vertical" />
                </div>
            </div>
        </nav>
        
        <!-- Main Content with Navigation Offset -->
        <main class="w-full">
            {{-- Page Content - using modern fluid design --}}
            <div class="w-full">
                {!! $renderedContent !!}
            </div>
        </main>
        
        <!-- Modern Footer -->
        <footer class="bg-gray-50">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-16">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div class="text-gray-600">
                        <p style="font-size: clamp(0.875rem, 2vw, 1rem); color: #6b7280;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </p>
                        <p class="text-sm mt-1" style="font-size: clamp(0.75rem, 1.5vw, 0.875rem); color: #9ca3af; margin-top: 0.25rem;">
                            Powered by TallCMS
                        </p>
                    </div>
                    
                    <x-menu location="footer" style="footer" class="flex items-center space-x-6" />
                </div>
            </div>
        </footer>
    </div>
</div>

@livewireScripts

{{-- Preview device-specific styling and behavior --}}
<style>
    /* Device-specific display rules for preview */
    .viewport-desktop .preview-desktop-menu {
        display: flex !important;
    }
    .viewport-desktop .preview-mobile-menu-btn {
        display: none !important;
    }
    .viewport-desktop .preview-mobile-menu {
        display: none !important;
    }

    .viewport-tablet .preview-desktop-menu {
        display: flex !important;
    }
    .viewport-tablet .preview-mobile-menu-btn {
        display: none !important;
    }
    .viewport-tablet .preview-mobile-menu {
        display: none !important;
    }

    .viewport-mobile .preview-desktop-menu {
        display: none !important;
    }
    .viewport-mobile .preview-mobile-menu-btn {
        display: flex !important;
    }
    /* Mobile menu visibility is controlled by Alpine.js x-show="open" */
</style>

{{-- Ensure Alpine.js is properly initialized --}}
<script>
    document.addEventListener('alpine:init', () => {
        console.log('Alpine.js initialized for preview');
    });
</script>