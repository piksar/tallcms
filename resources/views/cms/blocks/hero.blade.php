@php
    // Height mapping
    $heightClasses = [
        'small' => 'min-h-[40vh]',
        'medium' => 'min-h-[60vh]', 
        'large' => 'min-h-[80vh]',
        'full' => 'min-h-screen'
    ];
    
    // Text alignment mapping
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];
    
    $heightClass = $heightClasses[$height] ?? 'min-h-[60vh]';
    $alignmentClass = $alignmentClasses[$text_alignment] ?? 'text-center';
    $overlayOpacity = ($overlay_opacity ?? 40) / 100;
@endphp

<div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-purple-600 text-white {{ $heightClass }}" 
     style="position: relative; overflow: hidden; background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: white;">
    @if($background_image)
        <div class="absolute inset-0 z-0" 
             style="position: absolute; inset: 0; z-index: 0; 
             background-image: url('{{ Storage::url($background_image) }}'); 
             background-size: cover; 
             background-position: center;
             @if($parallax_effect ?? true) background-attachment: fixed; @endif">
            <div class="absolute inset-0 bg-black" 
                 style="position: absolute; inset: 0; background-color: rgba(0, 0, 0, {{ $overlayOpacity }});"></div>
        </div>
    @endif
    
    <div class="relative z-10 px-6 py-24 sm:px-12 sm:py-32 lg:px-16" 
         style="position: relative; z-index: 10; padding: 6rem 1.5rem;">
        <div class="mx-auto max-w-4xl {{ $alignmentClass }}" 
             style="max-width: 56rem; 
             @if($text_alignment === 'left')
                text-align: left; margin-right: auto; margin-left: 0;
             @elseif($text_alignment === 'right')
                text-align: right; margin-left: auto; margin-right: 0;
             @else
                text-align: center; margin: 0 auto;
             @endif
             ">
            @if($heading)
                <h1 class="text-4xl font-bold tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl" 
                    style="font-size: 2.25rem; font-weight: bold; line-height: 1.2; margin-bottom: 1.5rem; color: white; filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));">
                    {{ $heading }}
                </h1>
            @endif
            
            @if($subheading)
                <p class="mt-6 text-xl leading-8 text-white/90 drop-shadow-md" 
                   style="margin-top: 1.5rem; font-size: 1.25rem; line-height: 2; color: rgba(255, 255, 255, 0.9); filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));">
                    {{ $subheading }}
                </p>
            @endif
            
            @if($button_text)
                <div class="mt-10" style="margin-top: 2.5rem;">
                    <a href="{{ $button_url ?? '#' }}" 
                       class="inline-block rounded-lg bg-white/95 backdrop-blur-sm px-8 py-4 text-lg font-semibold text-gray-900 shadow-lg transition-all duration-300 hover:bg-white hover:shadow-xl hover:scale-105"
                       style="display: inline-block; border-radius: 0.5rem; background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px); padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; color: #111827; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); text-decoration: none; transition: all 0.3s ease;">
                        {{ $button_text }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>