@php
    use TallCms\Cms\Models\CmsPost;
    use TallCms\Cms\Models\CmsCategory;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    // Block configuration with defaults
    $configCategories = $categories ?? [];
    $layout = $layout ?? 'grid';
    $columns = $columns ?? '3';
    $postsCount = (int) ($posts_count ?? 6);
    $offset = (int) ($offset ?? 0);
    $sortBy = $sort_by ?? 'newest';
    $pinnedPosts = $pinned_posts ?? [];
    $featuredOnly = $featured_only ?? false;
    $showImage = $show_image ?? true;
    $showExcerpt = $show_excerpt ?? true;
    $showDate = $show_date ?? true;
    $showAuthor = $show_author ?? false;
    $showCategories = $show_categories ?? true;
    $showReadMore = $show_read_more ?? true;
    $emptyMessage = $empty_message ?? __('tallcms::frontend.no_posts_found');
    $firstSection = $first_section ?? false;
    $sectionBackground = $background ?? 'bg-base-100';
    $sectionPaddingClass = $padding ?? 'py-16';
    $isPreview = $isPreview ?? false;

    // Featured badge settings with static class mapping
    $showFeaturedBadge = $show_featured_badge ?? false;
    $featuredBadgeStyle = $featured_badge_style ?? 'badge';
    $featuredBadgeColorKey = $featured_badge_color ?? 'warning';
    $featuredCardStyle = $featured_card_style ?? 'default';

    // Static class maps for Tailwind JIT compatibility
    $badgeClassMap = [
        'primary' => 'badge-primary',
        'secondary' => 'badge-secondary',
        'accent' => 'badge-accent',
        'warning' => 'badge-warning',
    ];
    $textClassMap = [
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'warning' => 'text-warning',
    ];
    $bgClassMap = [
        'primary' => 'bg-primary text-primary-content',
        'secondary' => 'bg-secondary text-secondary-content',
        'accent' => 'bg-accent text-accent-content',
        'warning' => 'bg-warning text-warning-content',
    ];

    // Card style class maps for featured posts (Tailwind JIT safe)
    $borderClassMap = [
        'primary' => 'border-2 border-primary',
        'secondary' => 'border-2 border-secondary',
        'accent' => 'border-2 border-accent',
        'warning' => 'border-2 border-warning',
    ];
    $gradientClassMap = [
        'primary' => 'bg-gradient-to-br from-base-200 to-primary/10',
        'secondary' => 'bg-gradient-to-br from-base-200 to-secondary/10',
        'accent' => 'bg-gradient-to-br from-base-200 to-accent/10',
        'warning' => 'bg-gradient-to-br from-base-200 to-warning/10',
    ];
    $ringClassMap = [
        'primary' => 'ring-1 ring-primary/20',
        'secondary' => 'ring-1 ring-secondary/20',
        'accent' => 'ring-1 ring-accent/20',
        'warning' => 'ring-1 ring-warning/20',
    ];

    $badgeClass = $badgeClassMap[$featuredBadgeColorKey] ?? 'badge-warning';
    $textClass = $textClassMap[$featuredBadgeColorKey] ?? 'text-warning';
    $bgClass = $bgClassMap[$featuredBadgeColorKey] ?? 'bg-warning text-warning-content';

    // Pagination configuration
    $enablePagination = $enable_pagination ?? false;
    $perPage = (int) ($per_page ?? 12);

    // Block UUID for pagination - use persisted UUID or generate stable fallback
    $blockUuid = $block_uuid ?? null;
    if (!$blockUuid) {
        // Fallback: use a static counter to ensure unique IDs for multiple blocks on same page
        // Combined with page slug for cross-page uniqueness
        static $blockCounter = 0;
        $blockCounter++;
        $pageSlugForId = $cmsPageSlug ?? request()->route('slug') ?? 'home';
        $blockUuid = 'legacy-' . substr(md5($pageSlugForId), 0, 4) . '-' . $blockCounter;
    }
    $paginationParam = "posts_{$blockUuid}";

    // Get parent slug - try multiple sources in order of preference:
    // 1. Passed directly to template ($parentSlug)
    // 2. Shared view data from CmsPageRenderer ($cmsPageSlug)
    // 3. Route parameter as fallback (handles direct page views)
    $parentSlug = $parentSlug
        ?? ($cmsPageSlug ?? null)
        ?? request()->route('slug')
        ?? '';

    // Check for category filter from query string
    $filterCategorySlug = request()->query('category');
    $filterCategory = $filterCategorySlug
        ? (tallcms_i18n_enabled()
            ? CmsCategory::withLocalizedSlug($filterCategorySlug)->first()
            : CmsCategory::withSlug($filterCategorySlug)->first())
        : null;

    // Determine if we should show drafts (preview mode for authenticated users)
    $showDrafts = auth()->check() && ($isPreview || request()->has('preview'));

    // Build the base query
    $query = CmsPost::query()->with(['categories', 'author']);

    // Apply publish filter (allow drafts in preview mode)
    if (!$showDrafts) {
        $query->published();
    }

    // Apply category filter - query string takes precedence over config
    $activeCategories = $filterCategory ? [$filterCategory->id] : $configCategories;
    if (!empty($activeCategories)) {
        $query->whereHas('categories', function ($q) use ($activeCategories) {
            $q->whereIn('tallcms_categories.id', $activeCategories);
        });
    }

    // Featured only filter
    if ($featuredOnly) {
        $query->featured();
    }

    // Track if we're using pagination
    $isPaginated = false;
    $posts = collect();

    // Handle sorting and retrieval
    if ($sortBy === 'manual' && !empty($pinnedPosts)) {
        // Manual selection doesn't support pagination
        $manualQuery = CmsPost::query()->with(['categories', 'author']);

        if (!$showDrafts) {
            $manualQuery->published();
        }

        if (!empty($activeCategories)) {
            $manualQuery->whereHas('categories', function ($q) use ($activeCategories) {
                $q->whereIn('tallcms_categories.id', $activeCategories);
            });
        }

        $posts = $manualQuery
            ->whereIn('id', $pinnedPosts)
            ->get()
            ->sortBy(function ($post) use ($pinnedPosts) {
                return array_search($post->id, $pinnedPosts);
            })
            ->values()
            ->skip($offset)
            ->take($postsCount);
    } else {
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'title_asc':
                $query->orderByRaw('LOWER(title) ASC');
                break;
            case 'title_desc':
                $query->orderByRaw('LOWER(title) DESC');
                break;
            case 'featured_first':
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('published_at', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }

        // Use pagination or simple limit
        // Note: offset and pagination are mutually exclusive - offset is ignored when pagination is enabled
        if ($enablePagination && !$isPreview && $offset === 0) {
            $currentPage = (int) request()->input($paginationParam, 1);
            $posts = $query->paginate($perPage, ['*'], $paginationParam, $currentPage)
                ->withQueryString();
            $isPaginated = true;
        } else {
            // Simple limit/offset mode (no pagination)
            if ($offset > 0) {
                $query->skip($offset);
            }
            $posts = $query->limit($postsCount)->get();
        }
    }

    // Section spacing
    $sectionPadding = $firstSection ? 'pb-16' : $sectionPaddingClass;

    // Animation config
    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    // Grid column classes
    $gridColumnClass = match((string) ($columns ?? '3')) {
        '2' => 'grid-cols-1 sm:grid-cols-2',
        '3' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        '4' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
    };

    // Helper to generate post URL (uses localized URL helper)
    $getPostUrl = function($post) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        // Get the localized post slug
        $postSlug = tallcms_i18n_enabled()
            ? ($post->getTranslation('slug', app()->getLocale(), false) ?? $post->slug)
            : $post->slug;

        // Build full slug with parent
        $fullSlug = empty($parentSlug) ? $postSlug : $parentSlug . '/' . $postSlug;
        return tallcms_localized_url($fullSlug);
    };

    // Helper to generate category filter URL (uses localized URL helper)
    $getCategoryFilterUrl = function($category) use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        // Get the localized category slug
        $categorySlug = tallcms_i18n_enabled()
            ? ($category->getTranslation('slug', app()->getLocale(), false) ?? $category->slug)
            : $category->slug;

        if (empty($parentSlug)) {
            return tallcms_localized_url('/') . '?category=' . $categorySlug;
        }
        return tallcms_localized_url($parentSlug) . '?category=' . $categorySlug;
    };

    // Helper to generate clear filter URL (uses localized URL helper)
    $getClearFilterUrl = function() use ($parentSlug, $isPreview) {
        if ($isPreview) {
            return '#';
        }
        if (empty($parentSlug)) {
            return tallcms_localized_url('/');
        }
        return tallcms_localized_url($parentSlug);
    };
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="posts-block {{ $sectionPadding }} {{ $sectionBackground }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Active filter indicator --}}
        @if($filterCategory)
            <div class="mb-6 flex items-center gap-2">
                <span class="text-sm text-base-content/70">{{ __('tallcms::frontend.filtering_by') }}</span>
                <span class="badge badge-lg gap-1" style="background-color: {{ $filterCategory->color ?? 'var(--p)' }}20; color: {{ $filterCategory->color ?? 'var(--p)' }};">
                    {{ $filterCategory->name }}
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="ml-1 hover:opacity-70"
                        title="{{ __('tallcms::frontend.clear_filter') }}"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </a>
                </span>
            </div>
        @endif

        @if($posts->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-12">
                <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-base-content/40" />
                <p class="mt-4 text-lg text-base-content/70">
                    {{ $emptyMessage }}
                </p>
                @if($filterCategory)
                    <a
                        href="{{ $getClearFilterUrl() }}"
                        class="mt-4 inline-flex items-center link link-primary"
                    >{{ __('tallcms::frontend.view_all_posts') }}</a>
                @endif
            </div>
        @else
            @if($layout === 'featured-hero')
                {{-- Featured Hero + Grid Layout --}}
                @php
                    // Separate featured and regular posts
                    // Take first featured post for hero, put remaining featured + all regular in grid
                    $featuredPosts = $posts->filter(fn($p) => $p->is_featured);
                    $heroPost = $featuredPosts->first();
                    $remainingFeatured = $featuredPosts->skip(1);
                    $regularPosts = $posts->filter(fn($p) => !$p->is_featured);
                    // Combine remaining featured + regular for the grid, normalize keys for consistent stagger delays
                    $gridPosts = $remainingFeatured->concat($regularPosts)->values();
                @endphp

                {{-- Featured Hero Section (only if we have a featured post) --}}
                @if($heroPost)
                    @php
                        $heroUrl = $getPostUrl($heroPost);
                        $heroHasImage = $showImage && $heroPost->featured_image;
                    @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="0"
                    >
                        <div class="mb-8">
                            <article class="card {{ $heroHasImage ? 'lg:card-side' : '' }} bg-base-200 shadow-lg relative overflow-hidden">
                                @if($heroHasImage)
                                    <figure class="lg:w-1/2">
                                        <a href="{{ $heroUrl }}" class="block">
                                            <img src="{{ Storage::disk(cms_media_disk())->url($heroPost->featured_image) }}"
                                                 alt="{{ $heroPost->title }}"
                                                 class="w-full h-64 lg:h-80 object-cover">
                                        </a>
                                    </figure>
                                @endif
                                <div class="card-body {{ $heroHasImage ? 'lg:w-1/2' : '' }}">
                                    @if($showFeaturedBadge)
                                        <span class="badge {{ $badgeClass }} gap-1 w-fit">
                                            <x-heroicon-s-star class="w-3 h-3" />{{ __('tallcms::frontend.featured') }}</span>
                                    @endif

                                    @if($showCategories && $heroPost->categories->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($heroPost->categories->take(3) as $category)
                                                <a
                                                    href="{{ $getCategoryFilterUrl($category) }}"
                                                    class="badge badge-sm hover:opacity-80 transition-opacity"
                                                    style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                                >
                                                    {{ $category->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    <h2 class="card-title text-2xl lg:text-3xl">
                                        <a href="{{ $heroUrl }}" class="hover:underline text-base-content">
                                            {{ $heroPost->title }}
                                        </a>
                                    </h2>

                                    @if($showDate || $showAuthor)
                                        <div class="flex items-center gap-2 text-sm text-base-content/60">
                                            @if($showDate && $heroPost->published_at)
                                                <time datetime="{{ $heroPost->published_at->toISOString() }}">
                                                    {{ $heroPost->published_at->format('M j, Y') }}
                                                </time>
                                            @endif
                                            @if($showDate && $showAuthor && $heroPost->author)
                                                <span>&middot;</span>
                                            @endif
                                            @if($showAuthor && $heroPost->author)
                                                <span>{{ $heroPost->author->name }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if($showExcerpt && $heroPost->excerpt)
                                        <p class="text-base-content/70 line-clamp-3">{{ $heroPost->excerpt }}</p>
                                    @endif

                                    @if($showReadMore)
                                        <div class="card-actions justify-start mt-4">
                                            <a href="{{ $heroUrl }}" class="btn btn-primary">{{ __('tallcms::frontend.read_more') }}<x-heroicon-m-arrow-right class="w-4 h-4" />
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        </div>
                    </x-tallcms::animation-wrapper>
                @endif

                {{-- Grid for remaining posts (or all posts if no featured) --}}
                @php
                    $postsForGrid = $heroPost ? $gridPosts : $posts;
                    $heroOffset = $heroPost ? 1 : 0; // Offset animation delay if hero exists
                @endphp
                @if($postsForGrid->isNotEmpty())
                    <div class="grid gap-6 sm:gap-8 {{ $gridColumnClass }}">
                        @foreach($postsForGrid as $index => $post)
                            @php
                                $postUrl = $getPostUrl($post);
                                $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1 + $heroOffset)) : 0;
                                $isFeatured = $post->is_featured;
                                $baseCardClass = 'card shadow-sm hover:shadow-md transition-shadow duration-200 h-full relative';
                                $cardBgClass = 'bg-base-200';
                                $cardExtraClass = '';

                                if ($isFeatured && $featuredCardStyle !== 'default') {
                                    $cardExtraClass = match($featuredCardStyle) {
                                        'border' => $borderClassMap[$featuredBadgeColorKey] ?? 'border-2 border-warning',
                                        'gradient' => '',
                                        'elevated' => 'shadow-lg hover:shadow-xl ' . ($ringClassMap[$featuredBadgeColorKey] ?? 'ring-1 ring-warning/20'),
                                        default => '',
                                    };

                                    if ($featuredCardStyle === 'gradient') {
                                        $cardBgClass = $gradientClassMap[$featuredBadgeColorKey] ?? 'bg-gradient-to-br from-base-200 to-warning/10';
                                    }
                                }
                            @endphp
                            <x-tallcms::animation-wrapper
                                :animation="$animationType"
                                :duration="$animationDuration"
                                :use-parent="true"
                                :delay="$itemDelay"
                            >
                                <article class="{{ $baseCardClass }} {{ $cardBgClass }} {{ $cardExtraClass }}">
                                    {{-- Featured Badge --}}
                                    @if($showFeaturedBadge && $isFeatured)
                                        @if($featuredBadgeStyle === 'star')
                                            <div class="absolute top-2 right-2 z-10">
                                                <x-heroicon-s-star class="w-6 h-6 {{ $textClass }}" />
                                            </div>
                                        @elseif($featuredBadgeStyle === 'ribbon')
                                            <div class="absolute top-0 right-0 overflow-hidden w-20 h-20 z-10">
                                                <div class="absolute transform rotate-45 {{ $bgClass }} text-xs font-bold py-1 right-[-35px] top-[15px] w-[120px] text-center shadow-sm">{{ __('tallcms::frontend.featured') }}</div>
                                            </div>
                                        @else
                                            <div class="absolute top-2 left-2 z-10">
                                                <span class="badge {{ $badgeClass }} badge-sm gap-1">
                                                    <x-heroicon-s-star class="w-3 h-3" />{{ __('tallcms::frontend.featured') }}</span>
                                            </div>
                                        @endif
                                    @endif

                                    @if($showImage && $post->featured_image)
                                        <figure>
                                            <a href="{{ $postUrl }}" class="block">
                                                <img
                                                    src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                                    alt="{{ $post->title }}"
                                                    class="w-full h-48 object-cover"
                                                    loading="lazy"
                                                >
                                            </a>
                                        </figure>
                                    @endif

                                    <div class="card-body">
                                        @if($showCategories && $post->categories->isNotEmpty())
                                            <div class="flex flex-wrap gap-2 mb-2">
                                                @foreach($post->categories->take(3) as $category)
                                                    <a
                                                        href="{{ $getCategoryFilterUrl($category) }}"
                                                        class="badge badge-sm hover:opacity-80 transition-opacity"
                                                        style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                                    >
                                                        {{ $category->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif

                                        <h3 class="card-title text-lg">
                                            <a href="{{ $postUrl }}" class="hover:underline text-base-content">
                                                {{ $post->title }}
                                            </a>
                                        </h3>

                                        @if($showDate || $showAuthor)
                                            <div class="flex items-center gap-2 text-sm text-base-content/60">
                                                @if($showDate && $post->published_at)
                                                    <time datetime="{{ $post->published_at->toISOString() }}">
                                                        {{ $post->published_at->format('M j, Y') }}
                                                    </time>
                                                @endif
                                                @if($showDate && $showAuthor && $post->author)
                                                    <span>&middot;</span>
                                                @endif
                                                @if($showAuthor && $post->author)
                                                    <span>{{ $post->author->name }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if($showExcerpt && $post->excerpt)
                                            <p class="text-sm text-base-content/70 line-clamp-3">
                                                {{ $post->excerpt }}
                                            </p>
                                        @endif

                                        @if($showReadMore)
                                            <div class="card-actions justify-start mt-2">
                                                <a href="{{ $postUrl }}" class="link link-primary link-hover text-sm inline-flex items-center">{{ __('tallcms::frontend.read_more') }}<x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            </x-tallcms::animation-wrapper>
                        @endforeach
                    </div>
                @endif
            @elseif($layout === 'grid')
                {{-- Grid Layout --}}
                <div class="grid gap-6 sm:gap-8 {{ $gridColumnClass }}">
                    @foreach($posts as $index => $post)
                        @php
                            $postUrl = $getPostUrl($post);
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                            $isFeatured = $post->is_featured;
                            $baseCardClass = 'card shadow-sm hover:shadow-md transition-shadow duration-200 h-full relative';
                            $cardBgClass = 'bg-base-200';
                            $cardExtraClass = '';

                            if ($isFeatured && $featuredCardStyle !== 'default') {
                                $cardExtraClass = match($featuredCardStyle) {
                                    'border' => $borderClassMap[$featuredBadgeColorKey] ?? 'border-2 border-warning',
                                    'gradient' => '',
                                    'elevated' => 'shadow-lg hover:shadow-xl ' . ($ringClassMap[$featuredBadgeColorKey] ?? 'ring-1 ring-warning/20'),
                                    default => '',
                                };

                                if ($featuredCardStyle === 'gradient') {
                                    $cardBgClass = $gradientClassMap[$featuredBadgeColorKey] ?? 'bg-gradient-to-br from-base-200 to-warning/10';
                                }
                            }
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                        >
                            <article class="{{ $baseCardClass }} {{ $cardBgClass }} {{ $cardExtraClass }}">
                                {{-- Featured Badge --}}
                                @if($showFeaturedBadge && $isFeatured)
                                    @if($featuredBadgeStyle === 'star')
                                        <div class="absolute top-2 right-2 z-10">
                                            <x-heroicon-s-star class="w-6 h-6 {{ $textClass }}" />
                                        </div>
                                    @elseif($featuredBadgeStyle === 'ribbon')
                                        <div class="absolute top-0 right-0 overflow-hidden w-20 h-20 z-10">
                                            <div class="absolute transform rotate-45 {{ $bgClass }} text-xs font-bold py-1 right-[-35px] top-[15px] w-[120px] text-center shadow-sm">{{ __('tallcms::frontend.featured') }}</div>
                                        </div>
                                    @else
                                        <div class="absolute top-2 left-2 z-10">
                                            <span class="badge {{ $badgeClass }} badge-sm gap-1">
                                                <x-heroicon-s-star class="w-3 h-3" />{{ __('tallcms::frontend.featured') }}</span>
                                        </div>
                                    @endif
                                @endif

                            @if($showImage && $post->featured_image)
                                <figure>
                                    <a href="{{ $postUrl }}" class="block">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="w-full h-48 object-cover"
                                            loading="lazy"
                                        >
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body">
                                @if($showCategories && $post->categories->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="badge badge-sm hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="card-title text-lg">
                                    <a href="{{ $postUrl }}" class="hover:underline text-base-content">
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="flex items-center gap-2 text-sm text-base-content/60">
                                        @if($showDate && $post->published_at)
                                            <time datetime="{{ $post->published_at->toISOString() }}">
                                                {{ $post->published_at->format('M j, Y') }}
                                            </time>
                                        @endif
                                        @if($showDate && $showAuthor && $post->author)
                                            <span>&middot;</span>
                                        @endif
                                        @if($showAuthor && $post->author)
                                            <span>{{ $post->author->name }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($showExcerpt && $post->excerpt)
                                    <p class="text-sm text-base-content/70 line-clamp-3">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore)
                                    <div class="card-actions justify-start mt-2">
                                        <a href="{{ $postUrl }}" class="link link-primary link-hover text-sm inline-flex items-center">{{ __('tallcms::frontend.read_more') }}<x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>
            @else
                {{-- List Layout --}}
                <div class="space-y-6">
                    @foreach($posts as $index => $post)
                        @php
                            $postUrl = $getPostUrl($post);
                            $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                            $isFeatured = $post->is_featured;
                            $isCompact = $layout === 'compact-list';

                            // Base classes for list layout
                            $baseListClass = 'card card-side shadow-sm hover:shadow-md transition-shadow duration-200 relative';
                            $listBgClass = 'bg-base-200';
                            $listExtraClass = '';

                            if ($isCompact) {
                                $baseListClass = 'card card-side py-2 border-b border-base-300 relative';
                                $listBgClass = 'bg-transparent';
                                $listExtraClass = 'shadow-none';
                            } elseif ($isFeatured && $featuredCardStyle !== 'default') {
                                $listExtraClass = match($featuredCardStyle) {
                                    'border' => $borderClassMap[$featuredBadgeColorKey] ?? 'border-2 border-warning',
                                    'gradient' => '',
                                    'elevated' => 'shadow-lg hover:shadow-xl ' . ($ringClassMap[$featuredBadgeColorKey] ?? 'ring-1 ring-warning/20'),
                                    default => '',
                                };

                                if ($featuredCardStyle === 'gradient') {
                                    $listBgClass = $gradientClassMap[$featuredBadgeColorKey] ?? 'bg-gradient-to-br from-base-200 to-warning/10';
                                }
                            }
                        @endphp
                        <x-tallcms::animation-wrapper
                            :animation="$animationType"
                            :duration="$animationDuration"
                            :use-parent="true"
                            :delay="$itemDelay"
                        >
                            <article class="{{ $baseListClass }} {{ $listBgClass }} {{ $listExtraClass }}">
                                {{-- Featured Badge (list layout - only for non-compact) --}}
                                @if($showFeaturedBadge && $isFeatured && !$isCompact)
                                    @if($featuredBadgeStyle === 'star')
                                        <div class="absolute top-2 right-2 z-10">
                                            <x-heroicon-s-star class="w-6 h-6 {{ $textClass }}" />
                                        </div>
                                    @elseif($featuredBadgeStyle === 'ribbon')
                                        <div class="absolute top-0 right-0 overflow-hidden w-20 h-20 z-10">
                                            <div class="absolute transform rotate-45 {{ $bgClass }} text-xs font-bold py-1 right-[-35px] top-[15px] w-[120px] text-center shadow-sm">{{ __('tallcms::frontend.featured') }}</div>
                                        </div>
                                    @else
                                        <div class="absolute top-2 left-2 z-10">
                                            <span class="badge {{ $badgeClass }} badge-sm gap-1">
                                                <x-heroicon-s-star class="w-3 h-3" />{{ __('tallcms::frontend.featured') }}</span>
                                        </div>
                                    @endif
                                @endif

                            @if($showImage && $post->featured_image && !$isCompact)
                                <figure class="flex-shrink-0">
                                    <a href="{{ $postUrl }}">
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                            alt="{{ $post->title }}"
                                            class="w-48 h-32 object-cover rounded-l-xl"
                                            loading="lazy"
                                        >
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body py-4">
                                {{-- Inline featured indicator for compact list --}}
                                @if($showFeaturedBadge && $isFeatured && $isCompact)
                                    <span class="badge {{ $badgeClass }} badge-sm gap-1 w-fit mb-1">
                                        <x-heroicon-s-star class="w-3 h-3" />{{ __('tallcms::frontend.featured') }}</span>
                                @endif

                                @if($showCategories && $post->categories->isNotEmpty() && !$isCompact)
                                    <div class="flex flex-wrap gap-2 mb-1">
                                        @foreach($post->categories->take(3) as $category)
                                            <a
                                                href="{{ $getCategoryFilterUrl($category) }}"
                                                class="badge badge-sm hover:opacity-80 transition-opacity"
                                                style="background-color: {{ $category->color ?? 'var(--p)' }}20; color: {{ $category->color ?? 'var(--p)' }};"
                                            >
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <h3 class="card-title {{ $isCompact ? 'text-base' : 'text-xl' }}">
                                    <a href="{{ $postUrl }}" class="hover:underline text-base-content">
                                        {{ $post->title }}
                                    </a>
                                </h3>

                                @if($showDate || $showAuthor)
                                    <div class="flex items-center gap-2 text-sm text-base-content/60">
                                        @if($showDate && $post->published_at)
                                            <time datetime="{{ $post->published_at->toISOString() }}">
                                                {{ $post->published_at->format('M j, Y') }}
                                            </time>
                                        @endif
                                        @if($showDate && $showAuthor && $post->author)
                                            <span>&middot;</span>
                                        @endif
                                        @if($showAuthor && $post->author)
                                            <span>{{ $post->author->name }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($showExcerpt && $post->excerpt && !$isCompact)
                                    <p class="text-sm text-base-content/70 line-clamp-2">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                @if($showReadMore && !$isCompact)
                                    <div class="card-actions justify-start mt-1">
                                        <a href="{{ $postUrl }}" class="link link-primary link-hover text-sm inline-flex items-center">{{ __('tallcms::frontend.read_more') }}<x-heroicon-m-arrow-right class="w-4 h-4 ml-1" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                        </x-tallcms::animation-wrapper>
                    @endforeach
                </div>
            @endif

            {{-- Pagination (DaisyUI) --}}
            @if($isPaginated && $posts->hasPages())
                <div class="mt-8">
                    <x-tallcms::pagination :paginator="$posts" :paramName="$paginationParam" />
                </div>
            @endif
        @endif
    </div>
</x-tallcms::animation-wrapper>
