<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    {{-- Check if this is the welcome page --}}
    @if($renderedContent === 'WELCOME_PAGE')
        @include('welcome.tallcms')
    @else
        {{-- Regular Page Content --}}
        <article class="prose prose-lg max-w-none">
            {!! $renderedContent !!}
        </article>
    @endif
    
</div>