<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'agendamento';

    protected static ?string $pluralModelLabel = 'agendamentos';

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static ?int $navigationSort = 0;

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    /** Quantos atendimentos ainda ocupam a agenda hoje. */
    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()->blocking()->whereDate('starts_at', today())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
        ];
    }
}
