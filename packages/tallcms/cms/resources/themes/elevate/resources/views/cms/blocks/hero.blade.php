{{-- Elevate Hero System
     Every variant shares: premium typography, atmospheric depth, branded surfaces, intentional CTA composition.
     Variants: centered (flagship), with-form (acquisition), figure-left/right (product), bg-image (atmosphere).
--}}
@php
    use TallCms\Cms\Services\BlockLinkResolver;

    $isPreview = $isPreview ?? false;
    $currentLayout = $layout ?? 'centered';

    // Preserve RichEditor data-color → class transform
    $processedHeading = preg_replace('/data-color="([^"]+)"/', 'class="$1"', $heading ?? '');
    $processedSubheading = preg_replace('/data-color="([^"]+)"/', 'class="$1"', $subheading ?? '');

    // Background detection
    $hasBackgroundImage = ($background_image ?? null) && Storage::disk(cms_media_disk())->exists($background_image);
    $overlayOpacity = $overlay_opacity ?? 0.4;
    $hasFigureImage = ($figure_image ?? null) && Storage::disk(cms_media_disk())->exists($figure_image);

    // Dark background: bg image always dark; for colors, whitelist known light ones
    $lightBgs = ['bg-base-100', 'bg-base-200', 'bg-base-300', 'bg-info', 'bg-success', 'bg-warning', ''];
    $isDarkBackground = $hasBackgroundImage || !in_array($background_color ?? '', $lightBgs);

    // --- Shared Elevate Hero System ---

    // Typography: tighter, bolder, more controlled across ALL variants
    $heroHeadingClass = 'text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.1] mb-6';
    $heroSubheadingClass = 'text-lg sm:text-xl leading-relaxed mb-10 opacity-80';

    // Text colors adapt to background
    $textColorClass = $isDarkBackground ? 'text-neutral-content' : 'text-base-content';
    $microcopyColorClass = $isDarkBackground ? 'text-white/70' : 'text-base-content/70';

    // Buttons: respect user config, add pill shape as Elevate enhancement
    $buttonSize = $button_size ?? 'btn-lg';
    if (!$isDarkBackground) {
        $button_classes = $button_classes ?? "btn btn-primary rounded-full {$buttonSize}";
        $secondary_button_classes = $secondary_button_classes ?? "btn btn-ghost {$buttonSize}";
    }
    // Add rounded-full to user-configured buttons if not already present
    if (!str_contains($button_classes ?? '', 'rounded-full')) {
        $button_classes = str_replace('btn ', 'btn rounded-full ', $button_classes ?? "btn btn-primary {$buttonSize}");
    }
    if (!str_contains($secondary_button_classes ?? '', 'rounded-full')) {
        $secondary_button_classes = str_replace('btn ', 'btn rounded-full ', $secondary_button_classes ?? "btn btn-ghost {$buttonSize}");
    }

    // Gradient text: only on light backgrounds without image
    $useGradientText = !$hasBackgroundImage && !$isDarkBackground;

    // Asymmetric centered: only when no bg image and centered layout
    $useAsymmetric = $currentLayout === 'centered' && !$hasBackgroundImage;

    // Two-column layouts: only when there's actually a second column to show
    // figure-left/right without a figure image falls back to single-column
    $hasFigureColumn = in_array($currentLayout, ['figure-left', 'figure-right']) && $hasFigureImage;
    $isTwoColumn = $hasFigureColumn || $currentLayout === 'with-form' || $useAsymmetric;

    // Text alignment: two-column layouts default to text-left
    $effectiveAlignment = $isTwoColumn ? ($text_alignment ?? 'text-left') : ($text_alignment ?? 'text-center');
    $buttonAlignClass = match($effectiveAlignment) {
        'text-left' => 'justify-start',
        'text-right' => 'justify-end',
        default => 'justify-center',
    };
    $itemsAlignClass = match($effectiveAlignment) {
        'text-left' => 'items-start',
        'text-right' => 'items-end',
        default => 'items-center',
    };

    // Subheading width: narrower on two-column for better measure
    $subheadingMaxWidth = $isTwoColumn ? 'max-w-lg' : 'max-w-3xl';

    // Section classes
    $sectionClasses = "hero {$height} " . ($isPreview ? '' : '-mt-20') . " relative overflow-hidden " . ($css_classes ?? '');
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
    {{-- ═══════════════════════════════════════════
         BACKGROUND LAYER — shared across all variants
         ═══════════════════════════════════════════ --}}
    @if($hasBackgroundImage)
        <div class="absolute inset-0 z-0"
             style="background-image: url('{{ Storage::disk(cms_media_disk())->url($background_image) }}');
                    background-size: cover; background-position: center;
                    @if($parallax_effect ?? true) background-attachment: fixed; @endif">
            {{-- Branded overlay: gradient tint instead of flat black --}}
            <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(0,0,0,{{ $overlayOpacity * 1.1 }}), rgba(0,0,0,{{ $overlayOpacity * 0.7 }}));"></div>
        </div>
        {{-- Subtle vignette for text readability --}}
        <div class="absolute inset-0 z-0" style="background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.15) 100%);"></div>
    @else
        <div class="absolute inset-0 z-0 {{ $background_color ?? 'bg-base-100' }}"></div>
    @endif

    {{-- Atmospheric elements — every variant gets depth --}}
    @if(!$hasBackgroundImage)
        <div class="bg-dot-grid absolute inset-0 z-0"></div>
        <div class="glow-brand w-[600px] h-[600px] absolute -top-40 right-0 z-0"></div>
        @if($isTwoColumn)
            <div class="glow-brand w-[400px] h-[400px] absolute bottom-0 left-1/4 z-0" style="opacity: 0.05;"></div>
        @endif
    @endif

    {{-- ═══════════════════════════════════════════
         CONTENT LAYER
         ═══════════════════════════════════════════ --}}
    <div class="w-full px-4 sm:px-6 lg:px-8 py-24 sm:py-32 lg:py-40 relative z-10">
        <div class="max-w-7xl mx-auto {{ $isTwoColumn ? 'grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center' : '' }}">

            {{-- ─── TEXT COLUMN ─── --}}
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="{{ $textColorClass }} {{ !$isTwoColumn ? 'max-w-5xl mx-auto ' . $effectiveAlignment : '' }}"
            >
                @if($heading ?? null)
                    <h1 class="{{ $heroHeadingClass }} {{ $useGradientText ? 'text-gradient-primary' : '' }}"
                        @if($isDarkBackground) style="text-shadow: 0 2px 12px rgba(0,0,0,0.3)" @endif>
                        {!! $processedHeading !!}
                    </h1>
                @endif

                @if($subheading ?? null)
                    <div class="{{ $heroSubheadingClass }} {{ $subheadingMaxWidth }} {{ !$isTwoColumn && $effectiveAlignment === 'text-center' ? 'mx-auto' : '' }}">
                        {!! $processedSubheading !!}
                    </div>
                @endif

                {{-- Buttons render alongside the form card in with-form layout
                     so a hero can pair lead capture with secondary CTAs
                     (WhatsApp, demo link, etc.) instead of being mutually exclusive. --}}
                @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                    <div class="flex flex-col sm:flex-row gap-4 {{ $buttonAlignClass }}">
                        <div class="flex flex-col {{ $itemsAlignClass }} gap-2">
                            <a href="{{ e($button_url) }}" class="{{ $button_classes }}">
                                {{ $button_text }}
                            </a>
                            @if($button_microcopy ?? null)
                                <span class="text-sm {{ $microcopyColorClass }}">{{ $button_microcopy }}</span>
                            @endif
                        </div>
                        @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                            <div class="flex flex-col {{ $itemsAlignClass }} gap-2">
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

            {{-- ─── MEDIA / FORM COLUMN ─── --}}
            @if($isTwoColumn)
                <x-tallcms::animation-wrapper
                    :animation="$animationType"
                    :duration="$animationDuration"
                    :use-parent="true"
                    class="{{ $currentLayout === 'figure-left' ? 'lg:order-first' : '' }}"
                >
                    @if($currentLayout === 'with-form')
                        {{-- ═══ WITH-FORM: Premium acquisition panel ═══ --}}
                        @php
                            $normalizedFields = (isset($form_fields) && is_array($form_fields) && count($form_fields) > 0)
                                ? array_values($form_fields)
                                : \TallCms\Cms\Filament\Blocks\ContactFormBlock::getDefaultFields();
                            $formId = 'hero-contact-form-' . uniqid();
                            $formButtonStyle = $form_button_style ?? 'btn-primary';
                            $formSubmitText = $form_submit_text ?? __('tallcms::frontend.get_started');
                        @endphp
                        <div class="card {{ $form_card_style ?? 'bg-base-100/95 backdrop-blur-sm' }} shadow-2xl shadow-primary/5 border border-base-300/30 rounded-2xl w-full max-w-md mx-auto lg:mx-0 text-base-content">
                            <div class="card-body p-6 sm:p-8 text-base-content space-y-1">
                                @if($form_title ?? null)
                                    <h2 class="text-xl font-bold mb-1">{{ $form_title }}</h2>
                                @endif

                                @if($isPreview)
                                    <div class="space-y-4">
                                        @foreach($normalizedFields as $field)
                                            <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" :preview="true" />
                                        @endforeach
                                        <span class="btn {{ $formButtonStyle }} w-full rounded-full">{{ $formSubmitText }}</span>
                                    </div>
                                @else
                                    @php
                                        $formConfig = [
                                            'fields' => $normalizedFields,
                                            'submit_button_text' => $formSubmitText,
                                            'success_message' => $form_success_message ?? __('tallcms::frontend.thanks_well_be_in_touch'),
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
                                        <div x-show="formError" x-cloak class="alert alert-error mb-4"><span x-text="formError"></span></div>
                                        <div x-show="submitted" x-cloak class="alert alert-success"><span x-text="successMessage"></span></div>
                                        <form x-show="!submitted" x-on:submit.prevent="submit" class="space-y-4">
                                            @foreach($formConfig['fields'] as $field)
                                                <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" />
                                            @endforeach
                                            <div class="hidden" aria-hidden="true">
                                                <input type="text" x-model="formData._honeypot" tabindex="-1" autocomplete="off">
                                            </div>
                                            <button type="submit" class="btn {{ $formConfig['button_style'] }} w-full rounded-full" x-bind:disabled="submitting">
                                                <span x-show="!submitting">{{ $formConfig['submit_button_text'] }}</span>
                                                <span x-show="submitting" x-cloak class="loading loading-spinner loading-sm"></span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @elseif($hasFigureImage)
                        {{-- ═══ FIGURE: Productized media frame ═══ --}}
                        <div class="animate-float relative">
                            {{-- Glow behind figure --}}
                            <div class="glow-brand w-80 h-80 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0" style="opacity: 0.12;"></div>
                            <div class="relative z-10">
                                <img src="{{ Storage::disk(cms_media_disk())->url($figure_image) }}"
                                     alt="{{ $figure_alt ?? '' }}"
                                     class="max-w-md mx-auto {{ ($figure_rounded ?? true) ? 'rounded-2xl' : '' }} {{ ($figure_shadow ?? true) ? 'shadow-2xl shadow-primary/10' : '' }}" />
                            </div>
                        </div>

                    @elseif($useAsymmetric)
                        {{-- ═══ CENTERED NO-FIGURE: Decorative browser mockup ═══ --}}
                        <div class="animate-float hidden lg:block">
                            <div class="rounded-2xl border border-base-300/50 bg-base-200/50 shadow-2xl shadow-primary/5 overflow-hidden">
                                <div class="flex items-center gap-2 px-4 py-3 border-b border-base-300/30">
                                    <div class="flex gap-1.5">
                                        <div class="w-3 h-3 rounded-full bg-error/60"></div>
                                        <div class="w-3 h-3 rounded-full bg-warning/60"></div>
                                        <div class="w-3 h-3 rounded-full bg-success/60"></div>
                                    </div>
                                    <div class="flex-1 mx-8">
                                        <div class="h-5 bg-base-300/40 rounded-full"></div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4 bg-dot-grid relative" style="min-height: 280px;">
                                    <div class="h-4 w-3/4 rounded bg-primary/15"></div>
                                    <div class="h-3 w-full rounded bg-base-300/30"></div>
                                    <div class="h-3 w-5/6 rounded bg-base-300/30"></div>
                                    <div class="mt-6 grid grid-cols-3 gap-3">
                                        <div class="h-20 rounded-lg bg-primary/10"></div>
                                        <div class="h-20 rounded-lg bg-secondary/10"></div>
                                        <div class="h-20 rounded-lg bg-accent/10"></div>
                                    </div>
                                    <div class="flex gap-2 mt-4">
                                        <div class="h-8 w-24 rounded-full bg-primary/20"></div>
                                        <div class="h-8 w-20 rounded-full bg-base-300/20"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </x-tallcms::animation-wrapper>
            @endif

        </div>
    </div>

    {{-- Scroll Indicator --}}
    @if(($height ?? 'min-h-[70vh]') === 'min-h-screen')
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 {{ $isDarkBackground ? 'text-white/60' : 'text-base-content/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    @endif
</x-tallcms::animation-wrapper>
