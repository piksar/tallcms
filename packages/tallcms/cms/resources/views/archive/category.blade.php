@extends('tallcms::layouts.app')

@section('content')
<div class="pt-24 pb-16">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
        {{-- Archive Header --}}
        <header class="max-w-4xl mx-auto text-center mb-12">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Category</p>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-lg text-gray-600">{{ $category->description }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-4">
                {{ $posts->total() }} {{ Str::plural('post', $posts->total()) }}
            </p>
        </header>

        @if($posts->count() > 0)
            {{-- Post Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                @foreach($posts as $post)
                    @php
                        $postUrl = \TallCms\Cms\Services\SeoService::getPostUrl($post);
                    @endphp
                    <article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                        @if($post->featured_image)
                            <a href="{{ $postUrl }}">
                                <img src="{{ Storage::disk(cms_media_disk())->url($post->featured_image) }}"
                                     alt="{{ $post->title }}"
                                     class="w-full h-48 object-cover">
                            </a>
                        @endif
                        <div class="p-6">
                            <div class="flex items-center text-sm text-gray-500 mb-2">
                                @if($post->published_at)
                                    <time datetime="{{ $post->published_at->toISOString() }}">
                                        {{ $post->published_at->format('M j, Y') }}
                                    </time>
                                @endif
                                @if($post->author)
                                    <span class="mx-2">&middot;</span>
                                    <span>{{ $post->author->name }}</span>
                                @endif
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                                <a href="{{ $postUrl }}" class="hover:text-primary-600 transition-colors">
                                    {{ $post->title }}
                                </a>
                            </h2>
                            @if($post->excerpt)
                                <p class="text-gray-600 line-clamp-3">{{ $post->excerpt }}</p>
                            @endif
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($post->categories as $cat)
                                    <a href="{{ Route::has('tallcms.category.show') ? route('tallcms.category.show', $cat->slug) : url('/category/' . $cat->slug) }}"
                                       class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 transition-colors">
                                        {{ $cat->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination (DaisyUI) --}}
            @if($posts->hasPages())
                <div class="mt-12 max-w-7xl mx-auto">
                    <x-tallcms::pagination :paginator="$posts" />
                </div>
            @endif
        @else
            {{-- No Posts --}}
            <div class="text-center py-16">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">{{ __('tallcms::frontend.no_posts_found_heading') }}</h3>
                <p class="mt-2 text-gray-500">There are no published posts in this category yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
