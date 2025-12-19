<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\CmsPage;
use App\Services\BlockLinkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

class HeroBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'hero';
    }

    public static function getLabel(): string
    {
        return 'Hero';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the hero section with heading, subheading, and background image')
            ->schema([
                Tabs::make('Hero Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('heading')
                                    ->maxLength(255)
                                    ->placeholder('Enter hero heading'),
                                    
                                Textarea::make('subheading')
                                    ->maxLength(500)
                                    ->placeholder('Enter hero subheading or description'),
                                    
                                TextInput::make('button_text')
                                    ->maxLength(100)
                                    ->placeholder('Call to action button text')
                                    ->live(),
                                    
                                Select::make('button_link_type')
                                    ->label('Button Link Type')
                                    ->options([
                                        'page' => 'Page',
                                        'external' => 'External URL',
                                        'custom' => 'Custom URL',
                                    ])
                                    ->default('page')
                                    ->live()
                                    ->visible(fn (Get $get): bool => filled($get('button_text')))
                                    ->required(fn (Get $get): bool => filled($get('button_text')))
                                    ->afterStateUpdated(fn (callable $set) => $set('button_page_id', null))
                                    ->afterStateUpdated(fn (callable $set) => $set('button_url', null)),
                                    
                                Select::make('button_page_id')
                                    ->label('Select Page')
                                    ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('button_link_type') === 'page' && filled($get('button_text')))
                                    ->required(fn (Get $get): bool => $get('button_link_type') === 'page' && filled($get('button_text'))),
                                    
                                TextInput::make('button_url')
                                    ->label('URL')
                                    ->placeholder('https://example.com or /contact or #section')
                                    ->visible(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom']) && filled($get('button_text')))
                                    ->required(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom']) && filled($get('button_text'))),
                                    
                                TextInput::make('secondary_button_text')
                                    ->maxLength(100)
                                    ->placeholder('Secondary button text (optional)')
                                    ->live(),
                                    
                                Select::make('secondary_button_link_type')
                                    ->label('Secondary Button Link Type')
                                    ->options([
                                        'page' => 'Page',
                                        'external' => 'External URL',
                                        'custom' => 'Custom URL',
                                    ])
                                    ->default('page')
                                    ->live()
                                    ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                    ->required(fn (Get $get): bool => filled($get('secondary_button_text')))
                                    ->afterStateUpdated(fn (callable $set) => $set('secondary_button_page_id', null))
                                    ->afterStateUpdated(fn (callable $set) => $set('secondary_button_url', null)),
                                    
                                Select::make('secondary_button_page_id')
                                    ->label('Select Page')
                                    ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('secondary_button_link_type') === 'page' && filled($get('secondary_button_text')))
                                    ->required(fn (Get $get): bool => $get('secondary_button_link_type') === 'page' && filled($get('secondary_button_text'))),
                                    
                                TextInput::make('secondary_button_url')
                                    ->label('Secondary URL')
                                    ->placeholder('https://example.com or /contact or #section')
                                    ->visible(fn (Get $get): bool => in_array($get('secondary_button_link_type'), ['external', 'custom']) && filled($get('secondary_button_text')))
                                    ->required(fn (Get $get): bool => in_array($get('secondary_button_link_type'), ['external', 'custom']) && filled($get('secondary_button_text'))),
                            ]),
                            
                        Tab::make('Button Styling')
                            ->icon('heroicon-m-cursor-arrow-rays')
                            ->schema([
                                Section::make('Primary Button Styling')
                                    ->description('Customize the primary button colors')
                                    ->schema([
                                        Select::make('primary_button_style')
                                            ->label('Button Style')
                                            ->options([
                                                'preset' => 'Use Preset Colors',
                                                'custom' => 'Custom Colors',
                                            ])
                                            ->default('preset')
                                            ->live(),
                                            
                                        Select::make('primary_button_preset')
                                            ->label('Color Preset')
                                            ->options([
                                                'white' => 'White (Default)',
                                                'primary' => 'Primary Blue',
                                                'success' => 'Success Green',
                                                'warning' => 'Warning Orange',
                                                'danger' => 'Danger Red',
                                                'dark' => 'Dark',
                                            ])
                                            ->default('white')
                                            ->visible(fn (Get $get): bool => $get('primary_button_style') === 'preset'),
                                            
                                        ColorPicker::make('primary_button_bg_color')
                                            ->label('Background Color')
                                            ->default('#ffffff')
                                            ->visible(fn (Get $get): bool => $get('primary_button_style') === 'custom'),
                                            
                                        ColorPicker::make('primary_button_text_color')
                                            ->label('Text Color')
                                            ->default('#111827')
                                            ->visible(fn (Get $get): bool => $get('primary_button_style') === 'custom'),
                                    ])
                                    ->visible(fn (Get $get): bool => filled($get('button_text')))
                                    ->columns(2),
                                    
                                Section::make('Secondary Button Styling')
                                    ->description('Customize the secondary button colors')
                                    ->schema([
                                        Select::make('secondary_button_style')
                                            ->label('Button Style')
                                            ->options([
                                                'preset' => 'Use Preset Colors',
                                                'custom' => 'Custom Colors',
                                            ])
                                            ->default('preset')
                                            ->live(),
                                            
                                        Select::make('secondary_button_preset')
                                            ->label('Color Preset')
                                            ->options([
                                                'outline-white' => 'White Outline (Default)',
                                                'outline-primary' => 'Primary Blue Outline',
                                                'outline-success' => 'Success Green Outline',
                                                'outline-warning' => 'Warning Orange Outline',
                                                'outline-danger' => 'Danger Red Outline',
                                                'filled-white' => 'Filled White',
                                                'filled-primary' => 'Filled Primary Blue',
                                            ])
                                            ->default('outline-white')
                                            ->visible(fn (Get $get): bool => $get('secondary_button_style') === 'preset'),
                                            
                                        ColorPicker::make('secondary_button_bg_color')
                                            ->label('Background Color')
                                            ->default('#ffffff00') // Transparent
                                            ->visible(fn (Get $get): bool => $get('secondary_button_style') === 'custom'),
                                            
                                        ColorPicker::make('secondary_button_text_color')
                                            ->label('Text Color')
                                            ->default('#ffffff')
                                            ->visible(fn (Get $get): bool => $get('secondary_button_style') === 'custom'),
                                            
                                        ColorPicker::make('secondary_button_border_color')
                                            ->label('Border Color')
                                            ->default('#ffffff')
                                            ->visible(fn (Get $get): bool => $get('secondary_button_style') === 'custom'),
                                    ])
                                    ->visible(fn (Get $get): bool => filled($get('secondary_button_text')))
                                    ->columns(2),
                            ]),
                            
                        Tab::make('Background & Layout')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                FileUpload::make('background_image')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->directory('cms/hero-blocks')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->nullable()
                                    ->helperText('Recommended: 2560×1440px (16:9). Keep focal point centered to avoid cropping. Max 5MB. Formats: JPEG, PNG, WebP.'),
                                    
                                Toggle::make('parallax_effect')
                                    ->label('Enable Parallax Effect')
                                    ->default(true)
                                    ->helperText('Creates a scrolling effect where the background moves slower than content'),
                                    
                                Slider::make('overlay_opacity')
                                    ->label('Background Overlay Opacity')
                                    ->range(minValue: 0, maxValue: 100)
                                    ->step(5)
                                    ->tooltips(true)
                                    ->pips(PipsMode::Positions)
                                    ->pipsValues([0, 25, 50, 75, 100])
                                    ->fillTrack()
                                    ->helperText('Controls the darkness of the overlay on background images (0% = no overlay, 100% = full dark)'),
                                    
                                Select::make('text_alignment')
                                    ->label('Text Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center', 
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                                    
                                Select::make('height')
                                    ->label('Section Height')
                                    ->options([
                                        'small' => 'Small (40vh)',
                                        'medium' => 'Medium (60vh)',
                                        'large' => 'Large (80vh)', 
                                        'full' => 'Full screen (100vh)',
                                    ])
                                    ->default('medium'),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        // Pre-resolve URLs to avoid DB hits in view
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');
        
        return view('cms.blocks.hero', array_merge($config, [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? 'Hero Heading',
            'subheading' => $config['subheading'] ?? 'Hero subheading text',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $buttonUrl,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'background_image' => $config['background_image'] ?? null,
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => $config['overlay_opacity'] ?? '40',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'height' => $config['height'] ?? 'medium',
            // Primary button styling
            'primary_button_style' => $config['primary_button_style'] ?? 'preset',
            'primary_button_preset' => $config['primary_button_preset'] ?? 'white',
            'primary_button_bg_color' => $config['primary_button_bg_color'] ?? '#ffffff',
            'primary_button_text_color' => $config['primary_button_text_color'] ?? '#111827',
            // Secondary button styling
            'secondary_button_style' => $config['secondary_button_style'] ?? 'preset',
            'secondary_button_preset' => $config['secondary_button_preset'] ?? 'outline-white',
            'secondary_button_bg_color' => $config['secondary_button_bg_color'] ?? '#ffffff00',
            'secondary_button_text_color' => $config['secondary_button_text_color'] ?? '#ffffff',
            'secondary_button_border_color' => $config['secondary_button_border_color'] ?? '#ffffff',
        ]))->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        // Pre-resolve URLs to avoid DB hits in view
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        $secondaryButtonUrl = BlockLinkResolver::resolveButtonUrl($config, 'secondary_button');
        
        return view('cms.blocks.hero', array_merge($config, [
            'id' => static::getId(),
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $buttonUrl,
            'secondary_button_text' => $config['secondary_button_text'] ?? null,
            'secondary_button_url' => $secondaryButtonUrl,
            'background_image' => $config['background_image'] ?? null,
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => $config['overlay_opacity'] ?? '40',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'height' => $config['height'] ?? 'medium',
            // Primary button styling
            'primary_button_style' => $config['primary_button_style'] ?? 'preset',
            'primary_button_preset' => $config['primary_button_preset'] ?? 'white',
            'primary_button_bg_color' => $config['primary_button_bg_color'] ?? '#ffffff',
            'primary_button_text_color' => $config['primary_button_text_color'] ?? '#111827',
            // Secondary button styling
            'secondary_button_style' => $config['secondary_button_style'] ?? 'preset',
            'secondary_button_preset' => $config['secondary_button_preset'] ?? 'outline-white',
            'secondary_button_bg_color' => $config['secondary_button_bg_color'] ?? '#ffffff00',
            'secondary_button_text_color' => $config['secondary_button_text_color'] ?? '#ffffff',
            'secondary_button_border_color' => $config['secondary_button_border_color'] ?? '#ffffff',
        ]))->render();
    }
}
