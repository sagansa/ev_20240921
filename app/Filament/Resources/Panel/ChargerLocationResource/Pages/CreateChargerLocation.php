<?php

namespace App\Filament\Resources\Panel\ChargerLocationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\ChargerLocationResource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Forms\Form;

class CreateChargerLocation extends CreateRecord
{
    // use HasWizard;

    protected static string $resource = ChargerLocationResource::class;

    // public function form(Form $form): Form
    // {
    //     return parent::form($form)
    //         ->schema([
    //             Wizard::make($this->getSteps())
    //                 ->startOnStep($this->getStartStep())
    //                 ->cancelAction($this->getCancelFormAction())
    //                 ->submitAction($this->getSubmitFormAction())
    //                 ->skippable($this->hasSkippableSteps())
    //                 ->contained(false),
    //         ])
    //         ->columns(null);
    // }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (Auth::user()->hasRole('user')) {
            $data['status'] = 1;
        }

        return $data;
    }

    // protected function getSteps(): array
    // {
    //     return [
    //         Step::make('Location')
    //             ->schema([
    //                 Section::make()->schema(ChargerLocationResource::getDetailsFormHeadSchema())->columns(),
    //             ]),

    //         Step::make('Charger')
    //             ->schema([
    //                 Section::make()->schema([
    //                     ChargerLocationResource::getItemsRepeater(),
    //                 ]),
    //             ]),
    //     ];
    // }
}
