@php
    use App\Support\ThemeColors;
    
    // Get unified presets that work in both admin and frontend
    $buttonPresets = ThemeColors::getStaticButtonPresets();
    $textPresets = ThemeColors::getStaticTextPresets();
    $paddingPresets = ThemeColors::getStaticPaddingPresets();
    
    // Resolve button colors
    if (($button_style ?? 'preset') === 'preset') {
        $buttonPreset = $buttonPresets[$button_preset ?? 'primary'] ?? $buttonPresets['primary'];
        $buttonBgColor = $buttonPreset['bg'];
        $buttonTextColor = $buttonPreset['text'];
        $buttonHoverColor = $buttonPreset['hover'];
    } else {
        $buttonBgColor = $button_bg_color ?? $buttonPresets['primary']['bg'];
        $buttonTextColor = $button_text_color ?? $buttonPresets['primary']['text'];
        $buttonHoverColor = $buttonBgColor; // Use same as bg for custom colors
    }
    
    // Resolve text colors
    if (($text_color_style ?? 'theme') === 'theme') {
        $textPreset = $textPresets[$text_preset ?? 'primary'] ?? $textPresets['primary'];
        $resolvedHeadingColor = $textPreset['heading'];
        $resolvedDescriptionColor = $textPreset['description'];
    } else {
        $resolvedHeadingColor = $heading_color ?? $textPresets['primary']['heading'];
        $resolvedDescriptionColor = $description_color ?? $textPresets['primary']['description'];
    }
    
    // Text alignment mapping
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];
    
    // Resolve padding size
    $paddingSize = $padding ?? 'medium';
    $paddingPreset = $paddingPresets[$paddingSize] ?? $paddingPresets['medium'];
    $paddingClass = $paddingPreset['classes'];
    $paddingStyle = $paddingPreset['padding'];
    
    $alignmentClass = $alignmentClasses[$text_alignment] ?? 'text-center';
    
    // Background styling with theme colors
    $themeColors = ThemeColors::getColors();
    if (($background_style ?? 'color') === 'gradient') {
        $gradientFrom = $gradient_from ?? $themeColors['primary'][500];
        $gradientTo = $gradient_to ?? $themeColors['primary'][700];
        $backgroundStyle = "background: linear-gradient(135deg, {$gradientFrom} 0%, {$gradientTo} 100%);";
    } else {
        $bgColor = $background_color ?? $themeColors['neutral'][50];
        $backgroundStyle = "background-color: {$bgColor};";
    }
@endphp

<section class="{{ $paddingClass }}" 
         style="{{ $backgroundStyle }} padding: {{ $paddingStyle }};">
    <div class="mx-auto max-w-4xl {{ $alignmentClass }}" 
         style="max-width: 56rem; 
         @if($text_alignment === 'left')
            text-align: left; margin-right: auto; margin-left: 0;
         @elseif($text_alignment === 'right')
            text-align: right; margin-left: auto; margin-right: 0;
         @else
            text-align: center; margin: 0 auto;
         @endif">
        @if($title)
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl lg:text-5xl" 
                style="font-size: clamp(1.875rem, 4vw, 3rem); font-weight: bold; line-height: 1.2; color: {{ $resolvedHeadingColor }}; margin-bottom: 1.5rem;">
                {{ $title }}
            </h2>
        @endif
        
        @if($description)
            <p class="mt-6 text-lg leading-8 text-gray-600 {{ $text_alignment === 'center' ? 'max-w-2xl mx-auto' : 'max-w-2xl' }}" 
               style="margin-top: 1.5rem; font-size: 1.125rem; line-height: 1.75; color: {{ $resolvedDescriptionColor }}; @if($text_alignment === 'center') max-width: 42rem; margin-left: auto; margin-right: auto; @endif">
                {{ $description }}
            </p>
        @endif
        
        @if($button_text && $button_url && $button_url !== '#')
            <div class="mt-10 flex {{ $text_alignment === 'center' ? 'justify-center' : ($text_alignment === 'right' ? 'justify-end' : 'justify-start') }}" 
                 style="margin-top: 2.5rem; display: flex; 
                 @if($text_alignment === 'center')
                    justify-content: center;
                 @elseif($text_alignment === 'right')
                    justify-content: flex-end;
                 @else
                    justify-content: flex-start;
                 @endif">
                <a href="{{ e($button_url) }}" 
                   class="inline-flex items-center justify-center rounded-xl px-8 py-4 lg:px-10 lg:py-5 text-lg font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                   style="display: inline-flex; align-items: center; justify-content: center; background-color: {{ $buttonBgColor }}; color: {{ $buttonTextColor }}; border-radius: 0.75rem; padding: 1rem 2rem; font-size: clamp(1rem, 2vw, 1.125rem); font-weight: 600; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); text-decoration: none; transition: all 0.3s ease;"
                   onmouseover="this.style.backgroundColor='{{ $buttonHoverColor }}'"
                   onmouseout="this.style.backgroundColor='{{ $buttonBgColor }}'">
                    {{ $button_text }}
                    <svg class="ml-3 w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>