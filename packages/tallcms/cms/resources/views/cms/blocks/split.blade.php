@php
    use TallCms\Cms\Services\HtmlSanitizerService;

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
    $baseClasses = 'split-block w-full '.$sectionPadding.' '.($background ?? 'bg-base-100');
    $allClasses = trim($baseClasses.' '.($css_classes ?? ''));
    $animationType = ($isPreview ?? false) ? '' : ($animation_type ?? '');
    $animationDuration = $animation_duration ?? 'anim-duration-700';
    $animationStagger = ($isPreview ?? false) ? false : ($animation_stagger ?? false);
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="! ($isPreview ?? false)"
    :not-prose="false"
    :id="$anchor_id ?? null"
    class="{{ $allClasses }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-6xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        <div
            class="{{ $containerClass }}"
            data-split-layout="{{ $layoutPreset }}"
        >
            @foreach ($cells as $index => $cell)
                <x-tallcms::animation-wrapper
                    :animation="$animationType"
                    :duration="$animationDuration"
                    :use-parent="! ($isPreview ?? false)"
                    :delay="$animationStagger ? $index * $staggerDelay : 0"
                    tag="div"
                    @class([
                        $cell['class'],
                        'not-prose' => ($cell['type'] ?? '') === 'image',
                    ])
                    data-split-cell="{{ $index }}"
                    data-split-cell-type="{{ $cell['type'] ?? 'rich_text' }}"
                >
                    @if (($cell['type'] ?? '') === 'image')
                        @if (! empty($cell['image_url']))
                            <img
                                src="{{ $cell['image_url'] }}"
                                alt="{{ $cell['alt'] }}"
                                loading="lazy"
                                @class([
                                    $cell['image_class'] ?? 'w-full h-auto',
                                    'rounded-lg' => ($cell['image_shape'] ?? 'none') === 'rounded',
                                    'rounded-full object-cover aspect-square' => ($cell['image_shape'] ?? 'none') === 'circle',
                                ])
                            >
                        @elseif (! empty($cell['placeholder']))
                            <div
                                @class([
                                    $cell['image_class'] ?? 'w-full h-auto',
                                    'bg-base-300',
                                    'aspect-square rounded-full' => ($cell['image_shape'] ?? 'none') === 'circle',
                                    'aspect-[4/3] rounded-lg' => ($cell['image_shape'] ?? 'none') !== 'circle',
                                ])
                                aria-hidden="true"
                            ></div>
                        @endif
                    @else
                        @if (! empty($cell['body']))
                            <div class="prose prose-lg max-w-none text-base-content">
                                {!! HtmlSanitizerService::sanitizeTipTapContent($cell['body']) !!}
                            </div>
                        @endif
                    @endif
                </x-tallcms::animation-wrapper>
            @endforeach
        </div>
    </div>
</x-tallcms::animation-wrapper>
