<?php

namespace App\Filament\Resources\Professionals\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProfessionalForm
{
    /** Mesma convenção do Carbon e da migration: 0 = domingo ... 6 = sábado. */
    private const WEEKDAYS = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        0 => 'Domingo',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('active')
                            ->label('Ativo')
                            ->default(true)
                            ->helperText('Profissionais inativos não aparecem na página pública.'),

                        Select::make('services')
                            ->label('Serviços que realiza')
                            ->relationship('services', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Section::make('Horários de trabalho')
                    ->description('Para pausa de almoço, cadastre dois blocos no mesmo dia (ex.: 09:00-12:00 e 13:00-18:00).')
                    ->schema([
                        Repeater::make('workingHours')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                Select::make('weekday')
                                    ->label('Dia da semana')
                                    ->options(self::WEEKDAYS)
                                    ->required(),

                                TimePicker::make('start_time')
                                    ->label('Início')
                                    ->seconds(false)
                                    ->required(),

                                TimePicker::make('end_time')
                                    ->label('Fim')
                                    ->seconds(false)
                                    ->required()
                                    ->after('start_time'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar horário'),
                    ]),
            ]);
    }
}
