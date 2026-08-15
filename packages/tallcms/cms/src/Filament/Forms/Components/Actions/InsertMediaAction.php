<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms\Components\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use TallCms\Cms\Models\TallcmsMedia;

class InsertMediaAction
{
    public static function make(): Action
    {
        return Action::make('insertMedia')
            ->modalHeading(__('tallcms::fields.insert_from_media_library'))
            ->modalWidth(Width::FourExtraLarge)
            ->schema(fn (): array => [
                View::make('tallcms::filament.forms.components.media-library-picker')
                    ->viewData(fn () => [
                        'media' => TallcmsMedia::where('mime_type', 'like', 'image/%')
                            ->latest()
                            ->limit(100)
                            ->get(),
                    ]),
                Hidden::make('selected_media_id'),
                TextInput::make('alt')
                    ->label(__('tallcms::fields.alt_text'))
                    ->placeholder(__('tallcms::fields.alt_text_placeholder')),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $media = TallcmsMedia::find($data['selected_media_id']);

                if (! $media) {
                    return;
                }

                $component->runCommands(
                    [
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'image',
                            'attrs' => [
                                'id' => $media->id,
                                'src' => $media->url,
                                'alt' => filled($data['alt'] ?? null) ? $data['alt'] : ($media->alt_text ?? ''),
                            ],
                        ]]),
                    ],
                    editorSelection: $arguments['editorSelection'] ?? null,
                );
            });
    }
}
