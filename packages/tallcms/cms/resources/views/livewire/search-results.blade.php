<div class="min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h1 class="text-3xl font-bold mb-6">{{ __('tallcms::frontend.search') }}</h1>

        {{-- Scout not configured error --}}
        @if(!$searchAvailable)
            <div class="alert alert-warning mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ __('tallcms::frontend.search_not_configured') }}</span>
            </div>
        @endif

        {{-- Search Form --}}
        <div class="mb-8">
            <div class="join w-full max-w-xl">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="q"
                    placeholder="{{ __('tallcms::frontend.search_pages_and_posts') }}"
                    class="input input-bordered join-item flex-1"
                    minlength="{{ config('tallcms.search.min_query_length', 2) }}"
                    autofocus
                    @if(!$searchAvailable) disabled @endif
                />
                <button type="button" class="btn btn-primary join-item" @if(!$searchAvailable) disabled @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Type Filter --}}
        @if(strlen($q) >= config('tallcms.search.min_query_length', 2))
            <div class="tabs tabs-boxed mb-6 inline-flex">
                <button wire:click="$set('type', 'all')" class="tab {{ $type === 'all' ? 'tab-active' : '' }}">
                    {{ __('tallcms::frontend.search_all') }}
                </button>
                @if(in_array('pages', config('tallcms.search.searchable_types', ['pages', 'posts'])))
                    <button wire:click="$set('type', 'pages')" class="tab {{ $type === 'pages' ? 'tab-active' : '' }}">
                        {{ __('tallcms::frontend.search_pages') }}
                    </button>
                @endif
                @if(in_array('posts', config('tallcms.search.searchable_types', ['pages', 'posts'])))
                    <button wire:click="$set('type', 'posts')" class="tab {{ $type === 'posts' ? 'tab-active' : '' }}">
                        {{ __('tallcms::frontend.search_posts') }}
                    </button>
                @endif
            </div>
        @endif

        {{-- Results --}}
        @if(strlen($q) >= config('tallcms.search.min_query_length', 2))
            @if($results->isEmpty())
                <div class="alert">
                    <span>{{ __('tallcms::frontend.no_results_found_for') }} "{{ e($query) }}"</span>
                </div>
            @else
                <p class="text-sm text-base-content/70 mb-4">
                    {{ trans_choice(':count result|:count results', $results->total(), ['count' => $results->total()]) }}
                    {{ __('tallcms::frontend.for') }} "{{ e($query) }}"
                </p>

                <div class="space-y-4">
                    @foreach($results as $result)
                        <article class="card bg-base-200 hover:shadow-md transition-shadow">
                            <div class="card-body">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="badge badge-sm {{ $result['type'] === 'page' ? 'badge-primary' : 'badge-secondary' }}">
                                        {{ $result['type'] === 'page' ? __('tallcms::frontend.search_page') : __('tallcms::frontend.search_post') }}
                                    </span>
                                </div>
                                <h2 class="card-title text-lg">
                                    <a href="{{ $result['url'] }}" class="link link-hover" wire:navigate>
                                        {{ $result['model']->title }}
                                    </a>
                                </h2>
                                @if(!empty($result['excerpt']))
                                    <p class="text-base-content/70 text-sm">
                                        {!! $highlighter->highlight($result['excerpt'], $query) !!}
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($results->hasPages())
                    <div class="mt-8">
                        {{ $results->links() }}
                    </div>
                @endif
            @endif
        @else
            <p class="text-base-content/70">
                {{ __('Enter at least :min characters to search.', ['min' => config('tallcms.search.min_query_length', 2)]) }}
            </p>
        @endif
    </div>
</div>
