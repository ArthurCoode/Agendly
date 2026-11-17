<?php

namespace App\Filament\Resources\TimeOffs\Pages;

use App\Filament\Resources\TimeOffs\TimeOffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeOff extends CreateRecord
{
    protected static string $resource = TimeOffResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
