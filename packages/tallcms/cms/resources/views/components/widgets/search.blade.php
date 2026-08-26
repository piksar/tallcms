@props(['page' => null, 'renderedContent' => '', 'settings' => []])

@if(config('tallcms.search.enabled', true) && Route::has('tallcms.search'))
<div class="bg-base-100 rounded-lg p-4 shadow-sm">
    <h3 class="text-lg font-semibold mb-4">Search</h3>
    <form action="{{ route('tallcms.search') }}" method="GET">
        <div class="join w-full">
            <input
                type="search"
                name="q"
                placeholder="{{ __('tallcms::frontend.search_placeholder') }}"
                class="input input-bordered join-item w-full"
                minlength="{{ config('tallcms.search.min_query_length', 2) }}"
                required
            />
            <button type="submit" class="btn btn-primary join-item" aria-label="Search">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </div>
    </form>
</div>
@endif
