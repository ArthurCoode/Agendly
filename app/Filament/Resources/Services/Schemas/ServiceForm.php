<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('duration_minutes')
                    ->label('Duração')
                    ->required()
                    ->integer()
                    ->minValue(5)
                    ->maxValue(480)
                    ->step(5)
                    ->suffix('min')
                    ->helperText('É a duração que bloqueia a agenda do profissional.'),

                TextInput::make('price')
                    ->label('Preço')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('R$'),

                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true)
                    ->helperText('Desative em vez de excluir: serviços inativos somem da página pública, mas o histórico fica.'),
            ]);
    }
}
