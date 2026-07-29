<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules(['image'])
            ->hiddenLabel()
            ->nullable()
            ->openable()
            ->image()
            ->imageEditor()
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, self $component): ?string {
                return $this->storeAsWebp($file, $component);
            })
            ->columnSpan(['full'])
            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1']);
    }

    /**
     * Store the uploaded image on the public disk, converting to webp when possible.
     */
    protected function storeAsWebp(TemporaryUploadedFile $file, self $component): ?string
    {
        if (function_exists('imagewebp')) {
            $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

            if ($image !== false) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);

                ob_start();
                $saved = imagewebp($image, null, 85);
                $contents = ob_get_clean();
                imagedestroy($image);

                if ($saved && $contents !== false) {
                    $path = trim(($component->getDirectory() ?? '') . '/' . Str::ulid() . '.webp', '/');
                    $component->getDisk()->put($path, $contents);

                    if ($component->getVisibility() === 'public') {
                        rescue(fn () => $component->getDisk()->setVisibility($path, 'public'), report: false);
                    }

                    return $path;
                }
            }
        }

        // Fallback: store the original file on the component's disk.
        return $file->store($component->getDirectory(), $component->getDiskName());
    }
}
