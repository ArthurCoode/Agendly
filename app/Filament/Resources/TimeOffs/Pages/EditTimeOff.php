<?php

namespace App\Filament\Resources\TimeOffs\Pages;

use App\Filament\Resources\TimeOffs\TimeOffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeOff extends EditRecord
{
    protected static string $resource = TimeOffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
