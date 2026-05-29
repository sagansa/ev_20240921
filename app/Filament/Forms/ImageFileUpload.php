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
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): ?string {
                return $this->saveUploadedWebpFile($file);
            })
            ->disk('public')
            ->columnSpan([
                'full'
            ])
            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
        ;
    }

    public function saveUploadedWebpFile(TemporaryUploadedFile $file): ?string
    {
        if (! function_exists('imagewebp')) {
            return $this->saveUploadedFile($file);
        }

        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($image === false) {
            return $this->saveUploadedFile($file);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $saved = imagewebp($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        if (! $saved || $contents === false) {
            return $this->saveUploadedFile($file);
        }

        $path = trim($this->getDirectory() . '/' . Str::ulid() . '.webp', '/');
        $this->getDisk()->put($path, $contents);

        if ($this->getVisibility() === 'public') {
            rescue(fn () => $this->getDisk()->setVisibility($path, 'public'), report: false);
        }

        return $path;
    }
}
