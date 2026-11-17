<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['professional', 'service']))
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Horário')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (Appointment $record) => 'até '.$record->ends_at->format('H:i'))
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn (Appointment $record) => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_phone']),

                TextColumn::make('customer_email')
                    ->label('E-mail')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('service.name')
                    ->label('Serviço'),

                TextColumn::make('professional.name')
                    ->label('Profissional'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->defaultSort('starts_at')
            ->filters([
                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->relationship('professional', 'name')
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AppointmentStatus::class)
                    ->multiple(),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('date')->label('Dia'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['date'] ?? null,
                        fn (Builder $query, $date) => $query->whereDate('starts_at', $date),
                    ))
                    ->indicateUsing(fn (array $data) => ($data['date'] ?? null)
                        ? 'Dia '.Carbon::parse($data['date'])->format('d/m/Y')
                        : null),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::statusAction('confirm', AppointmentStatus::Confirmed, 'Confirmar', Heroicon::OutlinedCheck),
                    self::statusAction('complete', AppointmentStatus::Completed, 'Concluir', Heroicon::OutlinedCheckBadge),
                    self::statusAction('no_show', AppointmentStatus::NoShow, 'Marcar falta', Heroicon::OutlinedUserMinus),
                    self::statusAction('cancel', AppointmentStatus::Cancelled, 'Cancelar', Heroicon::OutlinedXMark)
                        ->requiresConfirmation()
                        ->modalDescription('O horário volta a ficar livre na página pública.'),
                ])->label('Status')->button(),
            ]);
    }

    /** Cada ação só aparece quando a transição é válida para o agendamento. */
    private static function statusAction(string $name, AppointmentStatus $target, string $label, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($target->getColor())
            ->visible(fn (Appointment $record) => $record->canTransitionTo($target))
            ->action(function (Appointment $record) use ($target) {
                $record->transitionTo($target);

                Notification::make()
                    ->title("Agendamento marcado como {$target->label()}.")
                    ->success()
                    ->send();
            });
    }
}
