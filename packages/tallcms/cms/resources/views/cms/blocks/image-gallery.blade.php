@php
    use TallCms\Cms\Services\MediaResolver;
    use Illuminate\Support\Facades\Storage;

    $layoutClasses = [
        'grid-1' => 'grid-cols-1',
        'grid-2' => 'grid-cols-1 md:grid-cols-2',
        'grid-3' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        'grid-4' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        'masonry' => 'columns-1 md:columns-2 lg:columns-3',
        'carousel' => 'flex gap-4 overflow-x-auto snap-x snap-mandatory',
    ];

    $sizeClasses = [
        'small' => 'h-48',
        'medium' => 'h-64',
        'large' => 'h-80',
        'full' => 'h-auto',
    ];

    $gridClass = $layoutClasses[$layout ?? 'grid-3'] ?? $layoutClasses['grid-3'];
    $sizeClass = $sizeClasses[$image_size ?? 'medium'] ?? $sizeClasses['medium'];
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
    $galleryId = 'gallery-' . uniqid();

    // Animation config
    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    // Resolve media from source
    $source = $source ?? 'manual';
    $mediaType = $media_type ?? 'images';

    if ($source === 'collection' && !empty($collection_ids)) {
        // Determine mime type filter based on media_type selection
        $mimeTypeFilter = match($mediaType) {
            'videos' => 'video/',
            'all' => null,
            default => 'image/',
        };

        $mediaItems = MediaResolver::fromCollections(
            $collection_ids,
            $collection_order ?? 'newest',
            $max_items ?? null,
            $mimeTypeFilter
        );
        $galleryItems = MediaResolver::toTemplateArray($mediaItems);
    } else {
        // Legacy manual mode - array of paths (images only)
        $galleryItems = collect($images ?? [])->filter(fn($path) =>
            Storage::disk(cms_media_disk())->exists($path)
        )->map(fn($path) => [
            'url' => Storage::disk(cms_media_disk())->url($path),
            'webp' => null,
            'alt' => '',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
        ])->values()->all();
    }

    // Get data for lightbox
    $lightboxData = collect($galleryItems)->map(fn($item) => [
        'url' => $item['url'],
        'alt' => $item['alt'] ?? '',
        'caption' => $item['caption'] ?? null,
        'type' => $item['type'] ?? 'image',
    ])->values()->all();

    // Pre-encode JSON for x-data - Js::from() outputs JSON.parse('...') with proper escaping
    // This safely handles quotes and special characters in HTML attribute context
    $lightboxDataJson = \Illuminate\Support\Js::from($lightboxData)->toHtml();

    // Build anchor ID attribute (avoid @if inside tag to prevent Blade comment injection)
    $anchorIdAttr = !empty($anchor_id) ? 'id="' . e($anchor_id) . '"' : '';
@endphp

<section
    {!! $anchorIdAttr !!}
    class="not-prose image-gallery-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
    x-data="{
        tallcmsShown: false,
        tallcmsReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        items: {!! $lightboxDataJson !!},
        currentIndex: 0,
        isOpen: false,
        open(index) {
            this.currentIndex = index;
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
            this.$refs.lightboxVideo?.pause();
        },
        next() {
            this.$refs.lightboxVideo?.pause();
            this.currentIndex = (this.currentIndex + 1) % this.items.length;
        },
        prev() {
            this.$refs.lightboxVideo?.pause();
            this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
        },
        get current() {
            return this.items[this.currentIndex] || {};
        }
    }"
    x-intersect:enter.once="tallcmsShown = true"
    @keydown.escape.window="close()"
    @keydown.left.window="isOpen && prev()"
    @keydown.right.window="isOpen && next()"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        @if($title ?? false)
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="mb-8"
            >
                <h3 class="text-2xl font-bold text-base-content text-center">
                    {{ $title }}
                </h3>
            </x-tallcms::animation-wrapper>
        @endif

        @if(($layout ?? 'grid-3') === 'masonry')
            <div class="{{ $gridClass }} gap-4">
                @foreach($galleryItems as $index => $item)
                    @php $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0; @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        class="break-inside-avoid mb-4"
                    >
                        <figure>
                            @if(($item['type'] ?? 'image') === 'video')
                                <div class="relative cursor-pointer group" @click="open({{ $index }})">
                                    <video
                                        src="{{ $item['url'] }}"
                                        class="w-full rounded-lg shadow-md group-hover:shadow-lg transition-shadow"
                                        muted
                                        preload="metadata"
                                    ></video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-16 h-16 bg-black/50 rounded-full flex items-center justify-center group-hover:bg-black/70 transition-colors">
                                            <x-heroicon-s-play class="w-8 h-8 text-white ml-1" />
                                        </div>
                                    </div>
                                </div>
                            @else
                                <picture>
                                    @if(!empty($item['webp']))
                                        <source srcset="{{ $item['webp'] }}" type="image/webp">
                                    @endif
                                    <img
                                        src="{{ $item['url'] }}"
                                        alt="{{ $item['alt'] ?: 'Gallery image ' . ($index + 1) }}"
                                        class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                        loading="lazy"
                                        @click="open({{ $index }})"
                                    >
                                </picture>
                            @endif
                            @if(!empty($item['caption']))
                                <figcaption class="mt-2 text-sm text-base-content/70 text-center">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @elseif(($layout ?? 'grid-3') === 'carousel')
            <div class="{{ $gridClass }} pb-4">
                @foreach($galleryItems as $index => $item)
                    @php $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0; @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        class="flex-none w-80 snap-start"
                    >
                        <figure>
                            @if(($item['type'] ?? 'image') === 'video')
                                <div class="relative cursor-pointer group" @click="open({{ $index }})">
                                    <video
                                        src="{{ $item['url'] }}"
                                        class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md group-hover:shadow-lg transition-shadow"
                                        muted
                                        preload="metadata"
                                    ></video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-16 h-16 bg-black/50 rounded-full flex items-center justify-center group-hover:bg-black/70 transition-colors">
                                            <x-heroicon-s-play class="w-8 h-8 text-white ml-1" />
                                        </div>
                                    </div>
                                </div>
                            @else
                                <picture>
                                    @if(!empty($item['webp']))
                                        <source srcset="{{ $item['webp'] }}" type="image/webp">
                                    @endif
                                    <img
                                        src="{{ $item['url'] }}"
                                        alt="{{ $item['alt'] ?: 'Gallery image ' . ($index + 1) }}"
                                        class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                        loading="lazy"
                                        @click="open({{ $index }})"
                                    >
                                </picture>
                            @endif
                            @if(!empty($item['caption']))
                                <figcaption class="mt-2 text-sm text-base-content/70 text-center">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @else
            <div class="grid {{ $gridClass }} gap-6">
                @foreach($galleryItems as $index => $item)
                    @php $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0; @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                    >
                        <figure>
                            @if(($item['type'] ?? 'image') === 'video')
                                <div class="relative cursor-pointer group" @click="open({{ $index }})">
                                    <video
                                        src="{{ $item['url'] }}"
                                        class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md group-hover:shadow-lg transition-shadow"
                                        muted
                                        preload="metadata"
                                    ></video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-16 h-16 bg-black/50 rounded-full flex items-center justify-center group-hover:bg-black/70 transition-colors">
                                            <x-heroicon-s-play class="w-8 h-8 text-white ml-1" />
                                        </div>
                                    </div>
                                </div>
                            @else
                                <picture>
                                    @if(!empty($item['webp']))
                                        <source srcset="{{ $item['webp'] }}" type="image/webp">
                                    @endif
                                    <img
                                        src="{{ $item['url'] }}"
                                        alt="{{ $item['alt'] ?: 'Gallery image ' . ($index + 1) }}"
                                        class="w-full {{ $sizeClass }} object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                        loading="lazy"
                                        @click="open({{ $index }})"
                                    >
                                </picture>
                            @endif
                            @if(!empty($item['caption']))
                                <figcaption class="mt-2 text-sm text-base-content/70 text-center">{{ $item['caption'] }}</figcaption>
                            @endif
                        </figure>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Lightbox Modal --}}
    <div
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
        @click.self="close()"
    >
        <div class="relative max-w-4xl max-h-full p-4 w-full">
            <figure class="text-center">
                <template x-if="current.type === 'video'">
                    <video
                        x-ref="lightboxVideo"
                        :src="current.url"
                        class="max-w-full max-h-[85vh] rounded-lg mx-auto"
                        controls
                        autoplay
                    ></video>
                </template>
                <template x-if="current.type !== 'video'">
                    <img
                        :src="current.url"
                        :alt="current.alt || 'Gallery image ' + (currentIndex + 1)"
                        class="max-w-full max-h-[85vh] rounded-lg mx-auto"
                    >
                </template>
                <figcaption
                    x-show="current.caption"
                    x-text="current.caption"
                    class="mt-4 text-white/90 text-sm"
                ></figcaption>
            </figure>

            {{-- Close Button --}}
            <button
                @click="close()"
                aria-label="{{ __('tallcms::frontend.close_gallery') }}"
                class="btn btn-circle btn-ghost text-white absolute top-4 right-4"
            >
                <x-heroicon-o-x-mark class="w-8 h-8" />
            </button>

            {{-- Previous Button --}}
            <button
                x-show="items.length > 1"
                @click="prev()"
                aria-label="{{ __('tallcms::frontend.previous_image') }}"
                class="btn btn-circle btn-ghost text-white absolute left-4 top-1/2 -translate-y-1/2"
            >
                <x-heroicon-o-chevron-left class="w-8 h-8" />
            </button>

            {{-- Next Button --}}
            <button
                x-show="items.length > 1"
                @click="next()"
                aria-label="{{ __('tallcms::frontend.next_image') }}"
                class="btn btn-circle btn-ghost text-white absolute right-4 top-1/2 -translate-y-1/2"
            >
                <x-heroicon-o-chevron-right class="w-8 h-8" />
            </button>
        </div>
    </div>
</section>
