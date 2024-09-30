<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;

class ImageFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules(['image'])
            ->hiddenLabel()
            ->nullable()
            ->openable()
            ->maxSize(1024)
            ->optimize('webp')
            ->image()
            ->imageEditor()
            ->disk('public')
            ->columnSpan([
                'full'
            ])
            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
            ;
    }
}
