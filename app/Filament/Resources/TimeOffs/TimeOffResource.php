<?php

namespace App\Filament\Resources\TimeOffs;

use App\Filament\Resources\TimeOffs\Pages\CreateTimeOff;
use App\Filament\Resources\TimeOffs\Pages\EditTimeOff;
use App\Filament\Resources\TimeOffs\Pages\ListTimeOffs;
use App\Filament\Resources\TimeOffs\Schemas\TimeOffForm;
use App\Filament\Resources\TimeOffs\Tables\TimeOffsTable;
use App\Models\TimeOff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeOffResource extends Resource
{
    protected static ?string $model = TimeOff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $modelLabel = 'folga';

    protected static ?string $pluralModelLabel = 'folgas e bloqueios';

    protected static ?string $navigationLabel = 'Folgas e bloqueios';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TimeOffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeOffsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimeOffs::route('/'),
            'create' => CreateTimeOff::route('/create'),
            'edit' => EditTimeOff::route('/{record}/edit'),
        ];
    }
}
