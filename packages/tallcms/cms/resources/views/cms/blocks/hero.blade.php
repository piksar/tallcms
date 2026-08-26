@php
    use TallCms\Cms\Services\BlockLinkResolver;

    $isPreview = $isPreview ?? false;

    // Preserve RichEditor data-color → class transform
    $processedHeading = preg_replace('/data-color="([^"]+)"/', 'class="$1"', $heading ?? '');
    $processedSubheading = preg_replace('/data-color="([^"]+)"/', 'class="$1"', $subheading ?? '');

    // Layout classes for hero-content
    $layoutClasses = match($layout ?? 'centered') {
        'figure-left' => 'flex-col lg:flex-row gap-8 lg:gap-12',
        'figure-right' => 'flex-col lg:flex-row-reverse gap-8 lg:gap-12',
        'with-form' => 'flex-col lg:flex-row gap-8 lg:gap-12',
        default => '',  // centered uses text-center on content div
    };

    // Button alignment based on text_alignment (preserved for all layouts)
    $buttonAlignClass = match($text_alignment ?? 'text-center') {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };

    $hasBackgroundImage = ($background_image ?? null) && Storage::disk(cms_media_disk())->exists($background_image);
    $hasOverlay = $hasBackgroundImage;

    // Overlay opacity: PHP already converts 0-100 to 0-1, receive as decimal
    $overlayOpacity = $overlay_opacity ?? 0.4;

    // Figure image existence check
    $hasFigureImage = ($figure_image ?? null) && Storage::disk(cms_media_disk())->exists($figure_image);

    // Determine if we're on a dark background (overlay or dark bg color)
    $isDarkBackground = $hasOverlay || in_array($background_color ?? '', [
        'bg-primary', 'bg-secondary', 'bg-accent', 'bg-neutral',
        'bg-gradient-to-br from-primary to-secondary'
    ]);

    // Text color class - applied to text content column ONLY (not hero-content, not form card)
    $textColorClass = $isDarkBackground ? 'text-neutral-content' : 'text-base-content';

    // Microcopy color class
    $microcopyColorClass = $isDarkBackground ? 'text-white/70' : 'text-base-content/70';

    // For light backgrounds, explicitly use standard button variants
    if (!$isDarkBackground) {
        $buttonSize = $button_size ?? 'btn-lg';
        $button_classes = "btn btn-primary {$buttonSize}";
        $secondary_button_classes = "btn btn-ghost {$buttonSize}";
    }

    // Parallax: iOS Safari renders background-attachment: fixed incorrectly
    // (image is sized to viewport, not element — appears zoomed/cropped).
    // The .parallax-block .parallax-bg rule in tallcms.css disables it on
    // touch/mobile/reduced-motion; opt the hero into that rule.
    $parallaxEnabled = $hasBackgroundImage && ($parallax_effect ?? true);

    // Section classes - keep -mt-20 for non-preview (tucks under nav)
    $sectionClasses = "hero {$height} " . ($isPreview ? '' : '-mt-20') . " relative overflow-hidden " . ($parallaxEnabled ? 'parallax-block ' : '') . ($css_classes ?? '');

    // Animation config
    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="{{ trim($sectionClasses) }}"
>
    {{-- Background (custom overlay div for opacity control, NOT .hero-overlay) --}}
    @if($hasBackgroundImage)
        <div class="absolute inset-0 z-0 {{ $parallaxEnabled ? 'parallax-bg' : '' }}"
             style="background-image: url('{{ Storage::disk(cms_media_disk())->url($background_image) }}');
                    background-size: cover; background-position: center;
                    @if($parallaxEnabled) background-attachment: fixed; @endif">
            <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, {{ $overlayOpacity }});"></div>
        </div>
    @else
        <div class="absolute inset-0 z-0 {{ $background_color ?? 'bg-gradient-to-br from-primary to-secondary' }}"></div>
    @endif

    {{-- Hero Content - NO textColorClass here, scoped to text column only --}}
    <div class="hero-content {{ $layoutClasses }} {{ ($layout ?? 'centered') === 'centered' ? ($text_alignment ?? 'text-center') : '' }} w-full px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40 relative z-10">

        {{-- Figure Image (figure layouts) - checks existence via $hasFigureImage --}}
        @if(in_array($layout ?? 'centered', ['figure-left', 'figure-right']) && $hasFigureImage)
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
            >
                <img src="{{ Storage::disk(cms_media_disk())->url($figure_image) }}"
                     alt="{{ $figure_alt ?? '' }}"
                     class="max-w-sm {{ ($figure_rounded ?? true) ? 'rounded-lg' : '' }} {{ ($figure_shadow ?? true) ? 'shadow-2xl' : '' }}" />
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Text Content - textColorClass scoped HERE only --}}
        <x-tallcms::animation-wrapper
            :animation="$animationType"
            :duration="$animationDuration"
            :use-parent="true"
            class="{{ $textColorClass }} {{ ($layout ?? 'centered') === 'centered' ? 'max-w-5xl mx-auto' : 'flex-1' }} {{ ($layout ?? 'centered') !== 'centered' ? ($text_alignment ?? 'text-left') : '' }}"
        >
            @if($heading ?? null)
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight mb-6" style="text-shadow: 0 4px 8px rgba(0,0,0,0.25)">
                    {!! $processedHeading !!}
                </h1>
            @endif

            @if($subheading ?? null)
                <div class="text-lg sm:text-xl lg:text-2xl leading-relaxed mb-10 opacity-85 max-w-3xl {{ ($layout ?? 'centered') === 'centered' ? 'mx-auto' : '' }}">
                    {!! $processedSubheading !!}
                </div>
            @endif

            {{-- Buttons render alongside the form card in with-form layout
                 so a hero can pair lead capture with secondary CTAs (WhatsApp,
                 demo link, etc.) without forcing operators into separate blocks. --}}
            @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                <div class="flex flex-col sm:flex-row gap-4 {{ $buttonAlignClass }}">
                    <div class="flex flex-col items-center gap-2">
                        <a href="{{ e($button_url) }}" class="{{ $button_classes }}">
                            {{ $button_text }}
                        </a>
                        @if($button_microcopy ?? null)
                            <span class="text-sm {{ $microcopyColorClass }}">{{ $button_microcopy }}</span>
                        @endif
                    </div>
                    {{-- Secondary button with microcopy --}}
                    @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                        <div class="flex flex-col items-center gap-2">
                            <a href="{{ e($secondary_button_url) }}" class="{{ $secondary_button_classes }}">
                                {{ $secondary_button_text }}
                            </a>
                            @if($secondary_button_microcopy ?? null)
                                <span class="text-sm {{ $microcopyColorClass }}">{{ $secondary_button_microcopy }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </x-tallcms::animation-wrapper>

        {{-- Form Card (with-form layout) --}}
        @if(($layout ?? 'centered') === 'with-form')
            @php
                // Normalize form fields - use ContactFormBlock defaults if empty
                // The $form_fields should already have defaults from HeroBlock, but double-check here
                $normalizedFields = (isset($form_fields) && is_array($form_fields) && count($form_fields) > 0)
                    ? array_values($form_fields)
                    : \TallCms\Cms\Filament\Blocks\ContactFormBlock::getDefaultFields();
                $formId = 'hero-contact-form-' . uniqid();
                $formButtonStyle = $form_button_style ?? 'btn-primary';
                $formSubmitText = $form_submit_text ?? __('tallcms::frontend.get_started');
            @endphp
            {{-- Form card with explicit text-base-content to override any inherited text color --}}
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="card {{ $form_card_style ?? 'bg-base-100 shadow-2xl' }} w-full max-w-md shrink-0 text-base-content"
            >
                <div class="card-body text-base-content">
                    @if($form_title ?? null)
                        <h2 class="card-title">{{ $form_title }}</h2>
                    @endif

                    @if($isPreview)
                        {{-- Static preview for admin --}}
                        <div class="space-y-4">
                            @foreach($normalizedFields as $field)
                                <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" :preview="true" />
                            @endforeach
                            <span class="btn {{ $formButtonStyle }} w-full">
                                {{ $formSubmitText }}
                            </span>
                        </div>
                    @else
                        {{-- Full Alpine form for frontend --}}
                        @php
                            $formConfig = [
                                'fields' => $normalizedFields,
                                'submit_button_text' => $formSubmitText,
                                'success_message' => $form_success_message ?? 'Thanks! We\'ll be in touch.',
                                'button_style' => $formButtonStyle,
                                'redirect_page_id' => $form_redirect_page_id ?? null,
                            ];
                            $pageUrl = request()->url();
                            $signature = \TallCms\Cms\Http\Controllers\ContactFormController::signConfig($formConfig, $pageUrl);
                            $jsConfig = [
                                'formId' => $formId,
                                'submitUrl' => route('tallcms.contact.submit'),
                                'successMessage' => $formConfig['success_message'],
                                'config' => $formConfig,
                                'signature' => $signature,
                                'pageUrl' => $pageUrl,
                                'fieldNames' => array_column($formConfig['fields'], 'name'),
                            ];
                        @endphp
                        <div id="{{ $formId }}" x-data="contactForm" data-contact-form-config='@json($jsConfig)' x-cloak>
                            {{-- Error/Success alerts --}}
                            <div x-show="formError" x-cloak class="alert alert-error mb-4">
                                <span x-text="formError"></span>
                            </div>
                            <div x-show="submitted" x-cloak class="alert alert-success">
                                <span x-text="successMessage"></span>
                            </div>
                            {{-- Form fields --}}
                            <form x-show="!submitted" x-on:submit.prevent="submit" class="space-y-4">
                                @foreach($formConfig['fields'] as $field)
                                    <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" />
                                @endforeach
                                <div class="hidden" aria-hidden="true">
                                    <input type="text" x-model="formData._honeypot" tabindex="-1" autocomplete="off">
                                </div>
                                <button type="submit" class="btn {{ $formConfig['button_style'] }} w-full" x-bind:disabled="submitting">
                                    <span x-show="!submitting">{{ $formConfig['submit_button_text'] }}</span>
                                    <span x-show="submitting" x-cloak class="loading loading-spinner loading-sm"></span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </x-tallcms::animation-wrapper>
        @endif
    </div>

    {{-- Scroll Indicator (full-screen height only) - color based on background --}}
    @if(($height ?? 'min-h-[70vh]') === 'min-h-screen')
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 {{ $isDarkBackground ? 'text-white/60' : 'text-base-content/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    @endif
</x-tallcms::animation-wrapper>
