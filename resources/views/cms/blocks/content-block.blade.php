@php
    use App\Services\HtmlSanitizerService;
    
    // Get current theme presets that work in both admin and frontend
    $textPresets = theme_text_presets();
    
    // Resolve text colors from theme system with fallbacks
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];
    
    // Content width classes
    $contentWidthClasses = match($content_width) {
        'narrow' => 'max-w-2xl mx-auto',
        'normal' => 'max-w-4xl mx-auto', 
        'wide' => 'max-w-6xl mx-auto',
        default => 'max-w-4xl mx-auto'
    };
    
    // Simple spacing system using Tailwind classes
    $sectionClasses = collect([
        'w-full',
        'px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16',
        $first_section ? 'pt-32 sm:pt-36 pb-12 sm:pb-16' : 'py-12 sm:py-16'
    ])->filter()->join(' ');

    // Build inline CSS custom properties for this block instance
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'], 
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';
@endphp

<article class="{{ $sectionClasses }}" style="{{ $customProperties }}">
    
    <div class="{{ $contentWidthClasses }}">
        
        @if($title || $subtitle)
            <header class="mb-8 sm:mb-10">
                @if($title)
                    <{{ $heading_level }} class="text-3xl sm:text-4xl font-bold leading-tight mb-4" 
                        style="color: var(--block-heading-color); font-size: clamp(1.875rem, 4vw, 2.25rem); line-height: 1.2;">
                        {{ $title }}
                    </{{ $heading_level }}>
                @endif
                
                @if($subtitle)
                    @php
                        $subtitleLevel = match($heading_level) {
                            'h2' => 'h3',
                            'h3' => 'h4', 
                            'h4' => 'h5',
                            default => 'h3'
                        };
                    @endphp
                    <{{ $subtitleLevel }} class="text-lg sm:text-xl mt-2" 
                        style="color: var(--block-text-color); font-size: 1.25rem; font-weight: normal; line-height: 1.4; margin-top: 0.75rem;">
                        {{ $subtitle }}
                    </{{ $subtitleLevel }}>
                @endif
            </header>
        @endif
        
        @if($body)
            <div class="content-block">
                {!! HtmlSanitizerService::sanitizeTipTapContent($body) !!}
            </div>
        @endif
        
    </div>
</article>