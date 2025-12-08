<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class ParallaxBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'parallax';
    }

    public static function getLabel(): string
    {
        return 'ParallaxBlock';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the parallax block')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter block title'),
                    
                Textarea::make('description')
                    ->maxLength(500)
                    ->placeholder('Enter block description'),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.parallax', [
            'title' => $config['title'] ?? 'Sample Title',
            'description' => $config['description'] ?? 'Sample description text',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.parallax', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}