{{-- Full page layout rendering for posts --}}
<div class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">
                            {{ config('app.name') }}
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <x-menu location="header" style="horizontal" class="preview-desktop-menu items-center space-x-8" />

                    <!-- Mobile Menu Button -->
                    <div class="preview-mobile-menu-btn flex items-center">
                        <button @click="open = !open" class="text-gray-700 p-2">
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
            <div x-show="open" x-cloak class="preview-mobile-menu border-t">
                <div class="px-4 py-3 space-y-3">
                    <x-menu location="header" style="vertical" />
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="py-8">
            {{-- Post Content - enhanced layout for posts --}}
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                {{-- Post Header --}}
                <header class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $post->title }}</h1>
                    
                    <div class="flex items-center text-sm text-gray-600 space-x-4">
                        @if($post->published_at)
                            <time datetime="{{ $post->published_at->toISOString() }}">
                                {{ $post->published_at->format('F j, Y') }}
                            </time>
                        @endif
                        
                        @if($post->author)
                            <span>by {{ $post->author }}</span>
                        @endif
                        
                        @if($post->categories->isNotEmpty())
                            <div class="flex items-center space-x-2">
                                <span>in</span>
                                @foreach($post->categories as $category)
                                    <span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </header>
                
                {{-- Post Content --}}
                <article class="prose prose-lg max-w-none">
                    {!! $renderedContent !!}
                </article>
                
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div class="text-gray-600">
                        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        <p class="text-sm mt-1">Powered by TallCMS</p>
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