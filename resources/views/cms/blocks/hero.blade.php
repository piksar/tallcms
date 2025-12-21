@php
    use App\Services\BlockLinkResolver;
    
    // Height mapping
    $heightClasses = [
        'small' => 'min-h-[50vh]',
        'medium' => 'min-h-[70vh]', 
        'large' => 'min-h-[90vh]',
        'full' => 'min-h-screen'
    ];
    
    // Text alignment mapping
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];
    
    // Get current theme presets
    $primaryButtonPresets = theme_button_presets();
    $secondaryButtonPresets = theme_button_presets();
    $themeColors = theme_colors();
    
    // Resolve primary button colors with fallbacks for custom themes
    if (($primary_button_style ?? 'preset') === 'preset') {
        $requestedPreset = $primary_button_preset ?? 'white';
        $primaryPreset = $primaryButtonPresets[$requestedPreset] 
            ?? $primaryButtonPresets['white'] 
            ?? $primaryButtonPresets['primary'] 
            ?? ['bg' => '#ffffff', 'text' => '#111827', 'hover' => '#f9fafb', 'border' => '#ffffff'];
        
        $primaryBgColor = $primaryPreset['bg'];
        $primaryTextColor = $primaryPreset['text'];
    } else {
        $primaryBgColor = $primary_button_bg_color ?? '#ffffff';
        $primaryTextColor = $primary_button_text_color ?? '#111827';
    }
    
    // Resolve secondary button colors with fallbacks for custom themes
    if (($secondary_button_style ?? 'preset') === 'preset') {
        $requestedSecondary = $secondary_button_preset ?? 'outline-white';
        $secondaryPreset = $secondaryButtonPresets[$requestedSecondary]
            ?? $secondaryButtonPresets['outline-white']
            ?? $secondaryButtonPresets['secondary']
            ?? ['bg' => 'rgba(255, 255, 255, 0)', 'text' => '#ffffff', 'hover' => 'rgba(255, 255, 255, 0.1)', 'border' => '#ffffff'];
        
        $secondaryBgColor = $secondaryPreset['bg'];
        $secondaryTextColor = $secondaryPreset['text'];
        $secondaryBorderColor = $secondaryPreset['border'] ?? $secondaryPreset['bg'] ?? '#ffffff';
    } else {
        $secondaryBgColor = $secondary_button_bg_color ?? '#ffffff00';
        $secondaryTextColor = $secondary_button_text_color ?? '#ffffff';
        $secondaryBorderColor = $secondary_button_border_color ?? '#ffffff';
    }
    
    $heightClass = $heightClasses[$height] ?? 'min-h-[70vh]';
    $alignmentClass = $alignmentClasses[$text_alignment] ?? 'text-center';
    $overlayOpacity = ($overlay_opacity ?? 40) / 100;
@endphp

<section class="relative overflow-hidden {{ $heightClass }} flex items-center -mt-20" 
         style="position: relative; overflow: hidden; display: flex; align-items: center;">
    
    {{-- Background Image or Gradient --}}
    @if($background_image && Storage::disk(cms_media_disk())->exists($background_image))
        <div class="absolute inset-0 z-0"
             style="position: absolute; inset: 0; z-index: 0;
             background-image: url('{{ Storage::disk(cms_media_disk())->url($background_image) }}'); 
             background-size: cover; 
             background-position: center;
             @if($parallax_effect ?? true) background-attachment: fixed; @endif">
            <div class="absolute inset-0" 
                 style="position: absolute; inset: 0; background-color: rgba(0, 0, 0, {{ $overlayOpacity }});"></div>
        </div>
    @else
        <div class="absolute inset-0 z-0 bg-gradient-to-br from-primary-600 to-primary-700" 
             style="position: absolute; inset: 0; z-index: 0; background: linear-gradient(135deg, {{ $themeColors['primary'][600] }} 0%, {{ $themeColors['primary'][700] }} 100%);"></div>
    @endif
    
    {{-- Content Container --}}
    <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 py-24 sm:py-32 lg:py-40" 
         style="position: relative; z-index: 10; width: 100%; padding-top: 6rem; padding-bottom: 6rem;">
        
        <div class="w-full {{ $alignmentClass }}" 
             style="width: 100%; 
             @if($text_alignment === 'left')
                text-align: left; margin-right: auto; margin-left: 0;
             @elseif($text_alignment === 'right')
                text-align: right; margin-left: auto; margin-right: 0;
             @else
                text-align: center; margin: 0 auto;
             @endif">
            
            {{-- Main Heading --}}
            @if($heading)
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl 2xl:text-8xl font-bold text-white leading-tight mb-6" 
                    style="font-size: clamp(2.5rem, 8vw, 7rem); font-weight: bold; line-height: 1.1; margin-bottom: 1.5rem; color: white; filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));">
                    {{ $heading }}
                </h1>
            @endif
            
            {{-- Subheading --}}
            @if($subheading)
                <p class="text-lg sm:text-xl lg:text-2xl xl:text-3xl text-white/80 leading-relaxed mb-10 {{ $text_alignment === 'center' ? 'max-w-5xl mx-auto' : 'max-w-4xl' }}" 
                   style="font-size: clamp(1.125rem, 3vw, 1.875rem); line-height: 1.6; color: rgba(255, 255, 255, 0.85); margin-bottom: 2.5rem;">
                    {{ $subheading }}
                </p>
            @endif
            
            {{-- Call to Action Buttons --}}
            @if(BlockLinkResolver::shouldRenderButton(get_defined_vars()))
                <div class="flex flex-col sm:flex-row gap-4 {{ $text_alignment === 'center' ? 'justify-center' : ($text_alignment === 'right' ? 'justify-end' : 'justify-start') }}" 
                     style="display: flex; gap: 1rem; 
                     @if($text_alignment === 'center')
                        justify-content: center; flex-direction: column; align-items: center;
                     @elseif($text_alignment === 'right')
                        justify-content: flex-end; flex-direction: column; align-items: flex-end;
                     @else
                        justify-content: flex-start; flex-direction: column; align-items: flex-start;
                     @endif">
                    
                    {{-- Primary Button --}}
                    <a href="{{ e($button_url) }}" 
                       class="inline-flex justify-center items-center px-8 py-4 lg:px-10 lg:py-5 rounded-xl font-semibold text-lg hover:opacity-90 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105"
                       style="display: inline-flex; justify-content: center; align-items: center; background-color: {{ $primaryBgColor }}; color: {{ $primaryTextColor }}; padding: 1rem 2rem; border-radius: 0.75rem; font-weight: 600; font-size: clamp(1rem, 2vw, 1.125rem); text-decoration: none; transition: all 0.3s ease; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);">
                        {{ $button_text }}
                        <svg class="ml-3 w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    
                    {{-- Secondary Button (if secondary text exists) --}}
                    @if(BlockLinkResolver::shouldRenderButton(get_defined_vars(), 'secondary_button'))
                        <a href="{{ e($secondary_button_url) }}" 
                           class="inline-flex justify-center items-center border-2 px-8 py-4 lg:px-10 lg:py-5 rounded-xl font-semibold text-lg hover:opacity-80 transition-all duration-300"
                           style="display: inline-flex; justify-content: center; align-items: center; background-color: {{ $secondaryBgColor }}; color: {{ $secondaryTextColor }}; border: 2px solid {{ $secondaryBorderColor }}; padding: 1rem 2rem; border-radius: 0.75rem; font-weight: 600; font-size: clamp(1rem, 2vw, 1.125rem); text-decoration: none; transition: all 0.3s ease;">
                            {{ $secondary_button_text }}
                        </a>
                    @endif
                </div>
            @endif
            
        </div>
    </div>
    
    {{-- Scroll Indicator (for full height heroes) --}}
    @if(($height ?? 'medium') === 'full')
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce" 
             style="position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%); animation: bounce 1s infinite;">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    @endif
</section>