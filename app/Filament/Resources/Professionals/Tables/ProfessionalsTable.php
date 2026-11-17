<?php

namespace App\Filament\Resources\Professionals\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProfessionalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('services.name')
                    ->label('Serviços')
                    ->badge(),

                TextColumn::make('working_hours_count')
                    ->label('Blocos de horário')
                    ->counts('workingHours'),

                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('active')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
