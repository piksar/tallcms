<?php

namespace TallCms\Cms\Filament\Resources\TallcmsMedia\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use TallCms\Cms\Filament\Resources\TallcmsMedia\TallcmsMediaResource;
use TallCms\Cms\Jobs\OptimizeMediaJob;
use TallCms\Cms\Services\ImageOptimizer;

class EditTallcmsMedia extends EditRecord
{
    protected static string $resource = TallcmsMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading(__('tallcms::ui.t_delete_media_file'))
                ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_delete_this_media_file_this_action_cannot_b'))
                ->modalSubmitActionLabel(__('tallcms::ui.t_delete_file')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $isImageReplacement = false;

        // Handle file replacement
        if (! empty($data['new_file'])) {
            $newFilePath = $data['new_file'];
            $oldPath = $record->path;
            $disk = \cms_media_disk();

            // Delete the old file if it exists
            if ($oldPath && Storage::disk($record->disk)->exists($oldPath)) {
                Storage::disk($record->disk)->delete($oldPath);
            }

            // Get new file info
            $originalName = pathinfo($newFilePath, PATHINFO_BASENAME);
            $mimeType = Storage::disk($disk)->mimeType($newFilePath);
            $size = Storage::disk($disk)->size($newFilePath);

            // Get image dimensions if it's an image
            $meta = $record->meta ?? [];
            if (str_starts_with($mimeType, 'image/')) {
                $isImageReplacement = true;
                if ($disk === 'public') {
                    $fullPath = Storage::disk($disk)->path($newFilePath);
                    if (file_exists($fullPath)) {
                        [$width, $height] = @getimagesize($fullPath);
                        if ($width && $height) {
                            $meta = array_merge($meta, ['width' => $width, 'height' => $height]);
                        }
                    }
                } else {
                    $tempPath = sys_get_temp_dir().'/'.uniqid('media_dim_').'.'.pathinfo($newFilePath, PATHINFO_EXTENSION);
                    try {
                        file_put_contents($tempPath, Storage::disk($disk)->get($newFilePath));
                        [$width, $height] = @getimagesize($tempPath);
                        if ($width && $height) {
                            $meta = array_merge($meta, ['width' => $width, 'height' => $height]);
                        }
                    } finally {
                        @unlink($tempPath);
                    }
                }
            }

            // Variants belong to the old file; drop them so URLs don't point at the wrong image.
            if ($record->has_variants) {
                app(ImageOptimizer::class)->deleteVariants($record);
            }
            unset($meta['variants']);

            // Update file metadata
            $data = array_merge($data, [
                'file_name' => $originalName,
                'mime_type' => $mimeType,
                'path' => $newFilePath,
                'disk' => $disk,
                'size' => $size,
                'meta' => $meta,
                'has_variants' => false,
                'optimized_at' => null,
            ]);
        }

        // Remove new_file from data as it's not a model field
        unset($data['new_file']);

        $record->update($data);

        if ($isImageReplacement && config('tallcms.media.optimization.enabled', true)) {
            OptimizeMediaJob::dispatch($record)
                ->onQueue(config('tallcms.media.optimization.queue', 'default'));
        }

        return $record;
    }
}
