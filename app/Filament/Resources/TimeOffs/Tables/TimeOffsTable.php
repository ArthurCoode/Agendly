<?php

namespace App\Filament\Resources\TimeOffs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeOffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('professional'))
            ->columns([
                TextColumn::make('professional.name')
                    ->label('Profissional')
                    ->searchable(),

                TextColumn::make('starts_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->placeholder('—'),
            ])
            ->defaultSort('starts_at')
            ->filters([
                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->relationship('professional', 'name')
                    ->preload(),

                // Por padrão esconde as que já passaram, para a lista não crescer sem fim.
                TernaryFilter::make('current')
                    ->label('Período')
                    ->placeholder('Todas')
                    ->trueLabel('Atuais e futuras')
                    ->falseLabel('Encerradas')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->where('ends_at', '>=', now()),
                        false: fn (Builder $query) => $query->where('ends_at', '<', now()),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
