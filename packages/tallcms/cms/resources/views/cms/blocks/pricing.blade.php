@php
    // Default values
    $plans = $plans ?? [];
    $section_title = $section_title ?? '';
    $section_subtitle = $section_subtitle ?? '';
    $columns = $columns ?? '3';
    $card_style = $card_style ?? 'shadow';
    $spacing = $spacing ?? 'normal';
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    // Grid classes based on columns
    $gridClasses = match((string) ($columns ?? '3')) {
        '1' => 'grid-cols-1',
        '2' => 'grid-cols-1 sm:grid-cols-2',
        '3' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        '4' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
    };

    // Spacing classes
    $gapClass = match($spacing) {
        'tight' => 'gap-4',
        'normal' => 'gap-6',
        'relaxed' => 'gap-8',
        default => 'gap-6'
    };

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-500';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);

    $accent = $accent_color ?? 'primary';
    $accentTint5 = \TallCms\Cms\Filament\Blocks\Support\AccentColor::tint5($accent);
    $accentBorder = \TallCms\Cms\Filament\Blocks\Support\AccentColor::border($accent);
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="pricing-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">

        {{-- Header Section --}}
        @if($section_title || $section_subtitle)
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="mb-12 {{ $text_alignment ?? 'text-center' }}"
            >
                @if($section_title)
                    <h2 class="text-3xl md:text-4xl font-bold mb-4 text-base-content">
                        {{ $section_title }}
                    </h2>
                @endif

                @if($section_subtitle)
                    <p class="text-lg md:text-xl text-base-content/70 max-w-3xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
                        {{ $section_subtitle }}
                    </p>
                @endif
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Pricing Plans Grid --}}
        @if(!empty($plans))
            <div class="grid {{ $gridClasses }} {{ $gapClass }}">
                @foreach($plans as $index => $plan)
                    @php
                        $isPopular = $plan['is_popular'] ?? false;
                        $planName = $plan['name'] ?? 'Plan';
                        $planDescription = $plan['description'] ?? '';
                        $currencySymbol = $plan['currency_symbol'] ?? '$';
                        $price = $plan['price'] ?? '0';
                        $billingPeriod = $plan['billing_period'] ?? 'month';
                        $discountText = $plan['discount_text'] ?? '';
                        $popularBadgeText = $plan['popular_badge_text'] ?? __('tallcms::frontend.most_popular');
                        $features = $plan['features'] ?? [];
                        $buttonText = $plan['button_text'] ?? __('tallcms::frontend.get_started');
                        $buttonUrl = $plan['button_url'] ?? '#';
                        $buttonStyle = $plan['button_style'] ?? 'btn-primary';
                        $trialText = $plan['trial_text'] ?? '';

                        // Card classes using daisyUI
                        $cardClasses = match($card_style) {
                            'bordered' => $isPopular
                                ? "card $accentTint5 border-2 $accentBorder"
                                : 'card bg-base-100 border-2 border-base-300',
                            'elevated' => $isPopular
                                ? "card $accentTint5 shadow-2xl scale-105 border-2 $accentBorder"
                                : 'card bg-base-200 shadow-lg',
                            default => $isPopular
                                ? "card $accentTint5 shadow-xl border-2 $accentBorder"
                                : 'card bg-base-200 shadow-lg',
                        };

                        // Button classes - use style from config directly
                        $buttonClasses = 'btn btn-block ' . $buttonStyle;

                        $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                    @endphp

                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        class="{{ $cardClasses }}"
                    >
                        <div class="card-body">

                            {{-- Popular Badge --}}
                            @if($isPopular && $popularBadgeText)
                                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                    <span class="badge badge-primary">
                                        {{ $popularBadgeText }}
                                    </span>
                                </div>
                            @endif

                            {{-- Plan Header --}}
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-bold mb-2 text-base-content">{{ $planName }}</h3>

                                @if($planDescription)
                                    <p class="text-sm text-base-content/70 mb-4">{{ $planDescription }}</p>
                                @endif

                                {{-- Price Display --}}
                                <div class="mb-2">
                                    @if($billingPeriod === 'free')
                                        <div class="text-4xl font-bold text-base-content">Free</div>
                                    @else
                                        <div class="flex items-baseline justify-center">
                                            <span class="text-lg text-base-content">{{ $currencySymbol }}</span>
                                            <span class="text-4xl font-bold text-base-content">{{ $price }}</span>
                                            @if($billingPeriod !== 'one-time')
                                                <span class="text-sm text-base-content/70 ml-1">/{{ str_replace('per ', '', $billingPeriod) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if($discountText)
                                    <div class="text-sm text-success font-medium">
                                        {{ $discountText }}
                                    </div>
                                @endif
                            </div>

                            {{-- Features List --}}
                            @if(!empty($features))
                                <ul class="space-y-3 mb-8 flex-grow">
                                    @foreach($features as $feature)
                                        @php
                                            $featureText = $feature['text'] ?? '';
                                            $isIncluded = $feature['included'] ?? true;
                                        @endphp

                                        @if($featureText)
                                            <li class="flex items-start gap-3">
                                                @if($isIncluded)
                                                    <x-heroicon-s-check class="w-5 h-5 text-success flex-shrink-0 mt-0.5" />
                                                @else
                                                    <x-heroicon-s-x-mark class="w-5 h-5 text-base-content/40 flex-shrink-0 mt-0.5" />
                                                @endif
                                                <span class="{{ $isIncluded ? 'text-base-content' : 'text-base-content/50 line-through' }}">
                                                    {{ $featureText }}
                                                </span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Call to Action --}}
                            <div class="card-actions justify-center mt-auto">
                                @if($buttonText)
                                    <a href="{{ e($buttonUrl) }}" class="{{ $buttonClasses }}">
                                        {{ $buttonText }}
                                    </a>
                                @endif

                                @if($trialText)
                                    <p class="text-xs text-base-content/60 mt-2 text-center w-full">
                                        {{ $trialText }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @endif

    </div>
</x-tallcms::animation-wrapper>
