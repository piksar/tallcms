@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $maxDepth = min(max((int) ($settings['max_depth'] ?? 3), 2), 4);

    // Check if the page has any Posts block (filtered or unfiltered)
    $groupedPosts = [];
    $postsBlockConfig = ($page) ? $page->getPostsBlockConfig() : [];

    if (!empty($postsBlockConfig)) {
        $parentSlug = ($page->slug === '/') ? '' : $page->slug;
        $categoryIds = $postsBlockConfig['categories'] ?? [];

        if (!empty($categoryIds)) {
            // Filtered: group posts by their configured categories
            $categories = \TallCms\Cms\Models\CmsCategory::whereIn('id', $categoryIds)
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $categoryIds))
                ->values();

            // Also collect categories from additional Posts blocks
            $allCategoryIds = $page->getPostsBlockCategoryIds();
            if (count($allCategoryIds) > count($categoryIds)) {
                $categories = \TallCms\Cms\Models\CmsCategory::whereIn('id', $allCategoryIds)
                    ->get()
                    ->sortBy(fn ($c) => array_search($c->id, $allCategoryIds))
                    ->values();
            }

            foreach ($categories as $category) {
                $query = \TallCms\Cms\Models\CmsPost::query()
                    ->published()
                    ->whereHas('categories', fn ($q) => $q->where('tallcms_categories.id', $category->id));

                // Mirror sort from block config
                match ($postsBlockConfig['sort_by'] ?? 'newest') {
                    'oldest' => $query->orderBy('published_at', 'asc'),
                    'title_asc' => $query->orderByRaw('LOWER(title) ASC'),
                    'title_desc' => $query->orderByRaw('LOWER(title) DESC'),
                    default => $query->orderBy('published_at', 'desc'),
                };

                $posts = $query->get();

                if ($posts->isNotEmpty()) {
                    $groupedPosts[] = [
                        'category' => $category,
                        'posts' => $posts,
                    ];
                }
            }
        } else {
            // Unfiltered: show all published posts in a single group
            $query = \TallCms\Cms\Models\CmsPost::query()->published();

            if ($postsBlockConfig['featured_only'] ?? false) {
                $query->featured();
            }

            $sortBy = $postsBlockConfig['sort_by'] ?? 'newest';
            $pinnedPosts = $postsBlockConfig['pinned_posts'] ?? [];

            if ($sortBy === 'manual' && !empty($pinnedPosts)) {
                $manualQuery = \TallCms\Cms\Models\CmsPost::query()->published();
                if ($postsBlockConfig['featured_only'] ?? false) {
                    $manualQuery->featured();
                }
                $posts = $manualQuery->whereIn('id', $pinnedPosts)->get()
                    ->sortBy(fn ($p) => array_search($p->id, $pinnedPosts))->values();
            } else {
                match ($sortBy) {
                    'oldest' => $query->orderBy('published_at', 'asc'),
                    'title_asc' => $query->orderByRaw('LOWER(title) ASC'),
                    'title_desc' => $query->orderByRaw('LOWER(title) DESC'),
                    default => $query->orderBy('published_at', 'desc'),
                };

                $offset = (int) ($postsBlockConfig['offset'] ?? 0);
                $count = (int) ($postsBlockConfig['posts_count'] ?? 50);
                $posts = $query->skip($offset)->take($count)->get();
            }

            if ($posts->isNotEmpty()) {
                $groupedPosts[] = [
                    'category' => null,
                    'posts' => $posts,
                ];
            }
        }
    }

    // Heading mode: fallback for pages without Posts blocks OR when posts are empty
    $headings = [];
    if (empty($groupedPosts)) {
        preg_match_all('/<h([2-' . $maxDepth . '])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $renderedContent, $headings, PREG_SET_ORDER);
    }

    // Current path for active state detection
    $currentPath = '/' . ltrim(request()->path(), '/');

    // Static indentation map to avoid Tailwind purging issues
    $indentClasses = [
        '2' => '',
        '3' => 'ml-4',
        '4' => 'ml-8',
    ];
@endphp

@if(!empty($groupedPosts))
    {{-- Posts by category mode --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="{{ __('tallcms::frontend.table_of_contents') }}">
        <h3 class="text-lg font-semibold mb-4">
            <a href="{{ tallcms_localized_url($parentSlug) }}" class="hover:text-primary transition-colors">{{ $page->title }}</a>
        </h3>
        <div class="space-y-5">
            @foreach($groupedPosts as $group)
                <div>
                    @if($group['category'])
                        <p class="text-[11px] font-bold uppercase tracking-widest text-base-content/40 mb-2">{{ $group['category']->name }}</p>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach($group['posts'] as $post)
                            @php
                                $postSlug = tallcms_i18n_enabled()
                                    ? ($post->getTranslation('slug', app()->getLocale(), false) ?? $post->slug)
                                    : $post->slug;
                                $fullSlug = empty($parentSlug) ? $postSlug : $parentSlug . '/' . $postSlug;
                                $postUrl = tallcms_localized_url($fullSlug);
                                $isActive = $currentPath === '/' . ltrim($postUrl, '/');
                            @endphp
                            <li>
                                <a
                                    href="{{ $postUrl }}"
                                    @class([
                                        'block rounded-lg px-3 py-1.5 text-sm transition-colors',
                                        'bg-primary/10 text-primary font-medium' => $isActive,
                                        'text-base-content/70 hover:text-base-content hover:bg-base-200/50' => !$isActive,
                                    ])
                                >
                                    {{ $post->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </nav>
@elseif(count($headings) > 0)
    {{-- Heading anchor mode (pages without Posts blocks) --}}
    <nav class="bg-base-100 rounded-lg p-4 shadow-sm sticky top-24" aria-label="{{ __('tallcms::frontend.table_of_contents') }}">
        <h3 class="text-lg font-semibold mb-4">On This Page</h3>
        <ul class="space-y-0.5">
            @foreach($headings as $heading)
                <li class="{{ $indentClasses[$heading[1]] ?? '' }}">
                    <a href="#{{ $heading[2] }}" class="block rounded-lg px-3 py-1.5 text-sm text-base-content/70 hover:text-base-content hover:bg-base-200/50 transition-colors">
                        {{ strip_tags($heading[3]) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
