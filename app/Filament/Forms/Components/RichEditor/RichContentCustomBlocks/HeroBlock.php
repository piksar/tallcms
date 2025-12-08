<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

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
                TextInput::make('heading')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter hero heading'),
                    
                Textarea::make('subheading')
                    ->maxLength(500)
                    ->placeholder('Enter hero subheading or description'),
                    
                TextInput::make('button_text')
                    ->maxLength(100)
                    ->placeholder('Call to action button text'),
                    
                TextInput::make('button_url')
                    ->url()
                    ->placeholder('https://example.com'),
                    
                FileUpload::make('background_image')
                    ->image()
                    ->directory('cms/hero-blocks')
                    ->disk('public')
                    ->visibility('public')
                    ->nullable(),
                    
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
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.hero', [
            'heading' => $config['heading'] ?? 'Hero Heading',
            'subheading' => $config['subheading'] ?? 'Hero subheading text',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $config['button_url'] ?? null,
            'background_image' => $config['background_image'] ?? null,
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => $config['overlay_opacity'] ?? '40',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'height' => $config['height'] ?? 'medium',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.hero', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'button_text' => $config['button_text'] ?? null,
            'button_url' => $config['button_url'] ?? null,
            'background_image' => $config['background_image'] ?? null,
            'parallax_effect' => $config['parallax_effect'] ?? true,
            'overlay_opacity' => $config['overlay_opacity'] ?? '40',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'height' => $config['height'] ?? 'medium',
        ])->render();
    }
}
