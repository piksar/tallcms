@php
    use App\Services\MergeTagService;
    use App\Services\HtmlSanitizerService;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\View;

    // Display settings from PostsBlock config
    $config = $config ?? [];
    $showExcerpt = $config['show_excerpt'] ?? true;
    $showDate = $config['show_date'] ?? true;
    $showAuthor = $config['show_author'] ?? false;
    $showImage = $config['show_image'] ?? true;
    $showCategories = $config['show_categories'] ?? true;
    $embedded = $embedded ?? false;


    // Save previous cmsPageSlug to restore after rendering (avoid global bleed)
    $previousCmsPageSlug = View::shared('cmsPageSlug');

    // Share parent page slug with blocks rendered within post content
    // This ensures Posts blocks inside posts use the correct parent page slug
    View::share('cmsPageSlug', $parentSlug ?? '');

    // Render post content blocks
    $renderedContent = '';
    if (!empty($post->content)) {
        $renderedContent = $post->renderRichContentUnsafe('content');
        $renderedContent = MergeTagService::replaceTags($renderedContent, $post);

        // Strip first heading if it matches the post title (avoid duplicate title display)
        $titlePattern = '/^\s*<h[1-2][^>]*>\s*' . preg_quote($post->title, '/') . '\s*<\/h[1-2]>\s*/i';
        $renderedContent = preg_replace($titlePattern, '', $renderedContent, 1);
    }

    // Restore previous cmsPageSlug
    View::share('cmsPageSlug', $previousCmsPageSlug);
@endphp

<article class="post-detail bg-base-100">
    {{-- Featured Image --}}
    @if($showImage && $post->featured_image)
        <div class="post-detail__hero relative w-full h-64 sm:h-80 md:h-96 overflow-hidden">
            <img
                src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>
    @endif

    {{-- Post Header --}}
    <header class="post-detail__header {{ $embedded ? '' : 'w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16' }} {{ $post->featured_image ? '-mt-24 relative z-10' : ($embedded ? 'pt-4' : 'pt-12 sm:pt-16') }}">
        <div class="{{ $embedded ? '' : 'max-w-4xl mx-auto' }} {{ $post->featured_image ? 'bg-base-100 rounded-t-2xl shadow-lg p-6 sm:p-10' : '' }}">
            {{-- Categories --}}
            @if($showCategories && $post->categories->isNotEmpty())
                <div class="post-detail__categories flex flex-wrap gap-2 mb-4">
                    @foreach($post->categories as $category)
                        @php
                            $categoryUrl = tallcms_localized_url($parentSlug ?? '') . '?category=' . $category->slug;
                        @endphp
                        <a
                            href="{{ $categoryUrl }}"
                            class="badge badge-lg hover:opacity-80 transition-opacity"
                            style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                        >
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Title --}}
            <h1 class="post-detail__title text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight mb-6 text-base-content">
                {{ $post->title }}
            </h1>

            {{-- Meta --}}
            @if($showAuthor || $showDate)
                <div class="post-detail__meta flex flex-wrap items-center gap-4 text-sm text-base-content/70">
                    @if($showAuthor && $post->author)
                        <div class="flex items-center gap-2">
                            <div class="avatar placeholder">
                                <div class="w-8 h-8 rounded-full bg-base-200">
                                    <span class="text-sm font-medium">
                                        {{ strtoupper(substr($post->author->name, 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                            <span class="font-medium">{{ $post->author->name }}</span>
                        </div>
                    @endif

                    @if($showDate && $post->published_at)
                        <div class="flex items-center gap-1">
                            <x-heroicon-o-calendar class="w-4 h-4" />
                            <time datetime="{{ $post->published_at->toISOString() }}">
                                {{ $post->published_at->format('F j, Y') }}
                            </time>
                        </div>
                    @endif

                    @if($showDate && $post->reading_time)
                        <div class="flex items-center gap-1">
                            <x-heroicon-o-clock class="w-4 h-4" />
                            <span>{{ __('tallcms::frontend.min_read', ['minutes' => $post->reading_time]) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Excerpt --}}
            @if($showExcerpt && $post->excerpt)
                <p class="post-detail__excerpt mt-6 text-lg leading-relaxed text-base-content/80">
                    {{ $post->excerpt }}
                </p>
            @endif
        </div>
    </header>

    {{-- Post Content --}}
    <div class="post-detail__content {{ $embedded ? 'py-6' : 'w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-8 sm:py-12' }}">
        <div class="{{ $embedded ? '' : 'max-w-4xl mx-auto' }}">
            @if($renderedContent)
                <div class="prose prose-lg max-w-none text-base-content">
                    {!! $renderedContent !!}
                </div>
            @else
                <p class="text-center py-8 text-base-content/70">
                    {{ __('tallcms::frontend.this_post_has_no_content') }}
                </p>
            @endif
        </div>
    </div>

    {{-- Comments --}}
    @if(config('tallcms.comments.enabled', true) && ($config['show_comments'] ?? true) && $post->isPublished())
        <div class="{{ $embedded ? '' : 'w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16' }}">
            <div class="{{ $embedded ? '' : 'max-w-4xl mx-auto' }}">
                <x-tallcms::comments :post="$post" />
            </div>
        </div>
    @endif

    {{-- Back Link --}}
    <footer class="post-detail__footer {{ $embedded ? 'py-6' : 'w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-12' }}">
        <div class="{{ $embedded ? '' : 'max-w-4xl mx-auto' }}">
            <a
                href="{{ tallcms_localized_url($parentSlug ?? '') }}"
                class="link link-primary inline-flex items-center gap-2 text-sm font-medium"
            >
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                {{ __('tallcms::frontend.back_to_all_posts') }}
            </a>
        </div>
    </footer>
</article>
