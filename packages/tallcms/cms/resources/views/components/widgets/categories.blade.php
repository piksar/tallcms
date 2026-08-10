@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@php
    $showCount = (bool) ($settings['show_count'] ?? true);

    // In multisite, categories are user-owned — this widget is disabled
    if (function_exists('tallcms_multisite_active') && tallcms_multisite_active()) {
        $categories = collect();
    } else {
        $categories = \TallCms\Cms\Models\CmsCategory::query()
            ->when($showCount, fn($q) => $q->withCount(['posts' => fn($q) => $q->published()]))
            ->orderBy('name')
            ->get();
    }

    // Use the current page for category filter URLs
    $currentPageSlug = $page?->slug ?? '';
    $blogUrl = tallcms_localized_url($currentPageSlug ?: '/');
@endphp

@if($categories->isNotEmpty())
<div class="bg-base-100 rounded-lg p-4 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">{{ __('tallcms::frontend.categories') }}</h3>
    <ul class="space-y-2">
        @foreach($categories as $category)
            @php
                $categorySlug = tallcms_i18n_enabled()
                    ? ($category->getTranslation('slug', app()->getLocale(), false) ?? $category->slug)
                    : $category->slug;
            @endphp
            <li class="flex justify-between items-center">
                <a href="{{ $blogUrl }}?category={{ $categorySlug }}" class="link link-hover text-sm">
                    {{ $category->name }}
                </a>
                @if($showCount)
                    <span class="badge badge-ghost badge-sm">{{ $category->posts_count }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif
