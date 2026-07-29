<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;
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
     * Filament fills the form from $record->attributesToArray(), which runs the
     * model's image accessor. That accessor returns a root-relative web URL
     * (e.g. "/storage/images/provider/x.webp") for API consumers, but the disk
     * only knows the relative path ("images/provider/x.webp"). Without this
     * normalization the parent existence check looks for
     * ".../app/public/storage/images/..." and drops the field — making the
     * preview vanish after every reload. Restore the relative DB path first.
     */
    public function hydrateFiles(): void
    {
        $this->rawState(
            array_map(
                fn (string $file) => $this->normalizeStoredPath($file),
                Arr::wrap($this->getRawState()),
            ),
        );

        parent::hydrateFiles();
    }

    protected function normalizeStoredPath(string $file): string
    {
        // Leave absolute/external URLs untouched (handled outside the disk).
        if (Str::startsWith($file, ['http://', 'https://', '//'])) {
            return $file;
        }

        $normalized = ltrim($file, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            return Str::after($normalized, 'storage/');
        }

        return $file;
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
