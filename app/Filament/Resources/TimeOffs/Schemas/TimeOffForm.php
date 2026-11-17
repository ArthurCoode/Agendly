<?php

namespace App\Filament\Resources\TimeOffs\Schemas;

use App\Models\TimeOff;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class TimeOffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('professional_id')
                    ->label('Profissional')
                    ->relationship('professional', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),

                TextInput::make('reason')
                    ->label('Motivo')
                    ->placeholder('Férias, consulta médica, feriado...')
                    ->maxLength(255),

                DateTimePicker::make('starts_at')
                    ->label('Início')
                    ->seconds(false)
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Fim')
                    ->seconds(false)
                    ->required()
                    ->after('starts_at')
                    ->rule(fn (Get $get): Closure => self::noActiveAppointments($get)),
            ])
            ->columns(2);
    }

    private static function noActiveAppointments(Get $get): Closure
    {
        return function (string $attribute, $value, Closure $fail) use ($get) {
            if (! $get('professional_id') || ! $get('starts_at') || ! $value) {
                return;
            }

            $conflicts = TimeOff::conflictingAppointments(
                (int) $get('professional_id'),
                Carbon::parse($get('starts_at')),
                Carbon::parse($value),
            )->orderBy('starts_at')->get();

            if ($conflicts->isEmpty()) {
                return;
            }

            $first = $conflicts->first()->starts_at->format('d/m H:i');

            $fail("Existem {$conflicts->count()} agendamento(s) ativo(s) nesse período (o primeiro em {$first}). Cancele ou remarque antes de bloquear.");
        };
    }
}
