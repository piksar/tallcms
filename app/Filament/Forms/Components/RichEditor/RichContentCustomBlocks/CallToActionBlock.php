<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\CmsPage;
use App\Services\BlockLinkResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class CallToActionBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'call_to_action';
    }

    public static function getLabel(): string
    {
        return 'Call to action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Create a compelling call to action section with custom styling')
            ->schema([
                Tabs::make('CTA Configuration')
                    ->tabs([
                        Tab::make('Content')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter CTA title'),
                                    
                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->placeholder('Enter CTA description'),
                                    
                                TextInput::make('button_text')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Get Started'),
                                    
                                Select::make('button_link_type')
                                    ->label('Button Link Type')
                                    ->options([
                                        'page' => 'Page',
                                        'external' => 'External URL',
                                        'custom' => 'Custom URL',
                                    ])
                                    ->default('page')
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('button_page_id', null))
                                    ->afterStateUpdated(fn (callable $set) => $set('button_url', null)),
                                    
                                Select::make('button_page_id')
                                    ->label('Select Page')
                                    ->options(CmsPage::where('status', 'published')->pluck('title', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->visible(fn (Get $get): bool => $get('button_link_type') === 'page'),
                                    
                                TextInput::make('button_url')
                                    ->label('URL')
                                    ->required()
                                    ->placeholder('https://example.com or /contact or #section')
                                    ->visible(fn (Get $get): bool => in_array($get('button_link_type'), ['external', 'custom'])),
                            ]),
                            
                        Tab::make('Button Styling')
                            ->icon('heroicon-m-cursor-arrow-rays')
                            ->schema([
                                Section::make('Button Styling')
                                    ->description('Customize the button appearance. ⚠️ Note: Preview uses default theme colors - actual colors may differ with custom themes.')
                                    ->schema([
                                        Select::make('button_style')
                                            ->label('Button Style')
                                            ->options([
                                                'preset' => 'Use Preset Colors',
                                                'custom' => 'Custom Colors',
                                            ])
                                            ->default('preset')
                                            ->live(),
                                            
                                        Select::make('button_preset')
                                            ->label('Color Preset')
                                            ->options([
                                                'primary' => 'Primary',
                                                'secondary' => 'Secondary', 
                                                'success' => 'Success',
                                                'warning' => 'Warning',
                                                'danger' => 'Danger',
                                                'neutral' => 'Neutral',
                                            ])
                                            ->default('primary')
                                            ->visible(fn (Get $get): bool => $get('button_style') === 'preset'),
                                            
                                        ColorPicker::make('button_bg_color')
                                            ->label('Background Color')
                                            ->default('#3b82f6')
                                            ->visible(fn (Get $get): bool => $get('button_style') === 'custom'),
                                            
                                        ColorPicker::make('button_text_color')
                                            ->label('Text Color')
                                            ->default('#ffffff')
                                            ->visible(fn (Get $get): bool => $get('button_style') === 'custom'),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        Tab::make('Background & Layout')
                            ->icon('heroicon-m-paint-brush')
                            ->schema([
                                Section::make('Background Styling')
                                    ->description('Configure the section background. 💡 Colors work with default theme - custom themes may override these settings.')
                                    ->schema([
                                        Select::make('background_style')
                                            ->label('Background Style')
                                            ->options([
                                                'color' => 'Solid Color',
                                                'gradient' => 'Gradient',
                                            ])
                                            ->default('color')
                                            ->live(),
                                            
                                        ColorPicker::make('background_color')
                                            ->label('Background Color')
                                            ->default('#f8fafc')
                                            ->visible(fn (Get $get): bool => $get('background_style') === 'color'),
                                            
                                        ColorPicker::make('gradient_from')
                                            ->label('Gradient From')
                                            ->default('#3b82f6')
                                            ->visible(fn (Get $get): bool => $get('background_style') === 'gradient'),
                                            
                                        ColorPicker::make('gradient_to')
                                            ->label('Gradient To')
                                            ->default('#8b5cf6')
                                            ->visible(fn (Get $get): bool => $get('background_style') === 'gradient'),
                                    ])
                                    ->columns(2),
                                    
                                Section::make('Text Colors')
                                    ->description('Configure text colors for proper contrast. ℹ️ Theme presets adapt to your site\'s color scheme automatically.')
                                    ->schema([
                                        Select::make('text_color_style')
                                            ->label('Text Color Style')
                                            ->options([
                                                'theme' => 'Use Theme Colors',
                                                'custom' => 'Custom Colors',
                                            ])
                                            ->default('theme')
                                            ->live(),
                                            
                                        Select::make('text_preset')
                                            ->label('Text Color Preset')
                                            ->options([
                                                'primary' => 'Primary Text (Dark on Light)',
                                                'secondary' => 'Secondary Text (Medium Contrast)',
                                                'muted' => 'Muted Text (Light Contrast)',
                                                'inverse' => 'Inverse Text (Light on Dark)',
                                            ])
                                            ->default('primary')
                                            ->visible(fn (Get $get): bool => $get('text_color_style') === 'theme'),
                                            
                                        ColorPicker::make('heading_color')
                                            ->label('Heading Color')
                                            ->default('#111827')
                                            ->visible(fn (Get $get): bool => $get('text_color_style') === 'custom'),
                                            
                                        ColorPicker::make('description_color')
                                            ->label('Description Color')
                                            ->default('#4b5563')
                                            ->visible(fn (Get $get): bool => $get('text_color_style') === 'custom'),
                                    ])
                                    ->columns(2),
                                    
                                Section::make('Layout Options')
                                    ->description('Configure alignment and spacing. ℹ️ Padding sizes show approximate values in preview - actual spacing adapts to screen size.')
                                    ->schema([
                                        Select::make('text_alignment')
                                            ->label('Text Alignment')
                                            ->options([
                                                'left' => 'Left',
                                                'center' => 'Center', 
                                                'right' => 'Right',
                                            ])
                                            ->default('center'),
                                            
                                        Select::make('padding')
                                            ->label('Padding Size')
                                            ->options([
                                                'small' => 'Small',
                                                'medium' => 'Medium',
                                                'large' => 'Large',
                                                'xl' => 'Extra Large',
                                            ])
                                            ->default('medium'),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        // Pre-resolve URL to avoid DB hits in view
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        
        return view('cms.blocks.call-to-action', array_merge($config, [
            'title' => $config['title'] ?? 'Call to Action Title',
            'description' => $config['description'] ?? 'Compelling description text',
            'button_text' => $config['button_text'] ?? 'Get Started',
            'button_url' => $buttonUrl,
            // Button styling
            'button_style' => $config['button_style'] ?? 'preset',
            'button_preset' => $config['button_preset'] ?? 'primary',
            'button_bg_color' => $config['button_bg_color'] ?? '#3b82f6',
            'button_text_color' => $config['button_text_color'] ?? '#ffffff',
            // Background styling
            'background_style' => $config['background_style'] ?? 'color',
            'background_color' => $config['background_color'] ?? '#f8fafc',
            'gradient_from' => $config['gradient_from'] ?? '#3b82f6',
            'gradient_to' => $config['gradient_to'] ?? '#8b5cf6',
            // Text colors
            'text_color_style' => $config['text_color_style'] ?? 'theme',
            'text_preset' => $config['text_preset'] ?? 'primary',
            'heading_color' => $config['heading_color'] ?? '#111827',
            'description_color' => $config['description_color'] ?? '#4b5563',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'padding' => $config['padding'] ?? 'medium',
        ]))->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        // Pre-resolve URL to avoid DB hit in view
        $buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
        
        return view('cms.blocks.call-to-action', array_merge($config, [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? '',
            'button_url' => $buttonUrl,
            // Button styling
            'button_style' => $config['button_style'] ?? 'preset',
            'button_preset' => $config['button_preset'] ?? 'primary',
            'button_bg_color' => $config['button_bg_color'] ?? '#3b82f6',
            'button_text_color' => $config['button_text_color'] ?? '#ffffff',
            // Background styling
            'background_style' => $config['background_style'] ?? 'color',
            'background_color' => $config['background_color'] ?? '#f8fafc',
            'gradient_from' => $config['gradient_from'] ?? '#3b82f6',
            'gradient_to' => $config['gradient_to'] ?? '#8b5cf6',
            // Text colors
            'text_color_style' => $config['text_color_style'] ?? 'theme',
            'text_preset' => $config['text_preset'] ?? 'primary',
            'heading_color' => $config['heading_color'] ?? '#111827',
            'description_color' => $config['description_color'] ?? '#4b5563',
            'text_alignment' => $config['text_alignment'] ?? 'center',
            'padding' => $config['padding'] ?? 'medium',
        ]))->render();
    }
}
