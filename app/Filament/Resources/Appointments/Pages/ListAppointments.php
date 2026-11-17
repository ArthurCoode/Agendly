<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\Actions\BookAppointmentAction;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookAppointmentAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Hoje')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today())),

            'upcoming' => Tab::make('Próximos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('starts_at', '>=', now())),

            'past' => Tab::make('Anteriores')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('starts_at', '<', now())),

            'all' => Tab::make('Todos'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'today';
    }
}
