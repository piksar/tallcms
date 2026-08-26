@props(['placeholder' => null])

@if(config('tallcms.search.enabled', true))
<form action="{{ tallcms_search_url() }}" method="GET" {{ $attributes }}>
    <input
        type="search"
        name="q"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder ?? __('tallcms::frontend.search_placeholder') }}"
        class="input input-bordered w-full"
        minlength="{{ config('tallcms.search.min_query_length', 2) }}"
    />
</form>
@endif
