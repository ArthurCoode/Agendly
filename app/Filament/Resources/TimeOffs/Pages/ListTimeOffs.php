<?php

namespace App\Filament\Resources\TimeOffs\Pages;

use App\Filament\Resources\TimeOffs\TimeOffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimeOffs extends ListRecords
{
    protected static string $resource = TimeOffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
