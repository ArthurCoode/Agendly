<?php

namespace App\Filament\Resources\Appointments\Actions;

use App\Exceptions\SlotUnavailableException;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Agendamento feito pelo estabelecimento (telefone, balcão).
 * Usa o mesmo BookingService da página pública: mesma trava contra conflito.
 */
class BookAppointmentAction
{
    public static function make(): Action
    {
        return Action::make('book')
            ->label('Novo agendamento')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Novo agendamento')
            ->modalSubmitActionLabel('Agendar')
            ->schema([
                Section::make('Horário')
                    ->columns(2)
                    ->schema([
                        Select::make('service_id')
                            ->label('Serviço')
                            ->options(fn () => Service::where('active', true)->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('professional_id', null);
                                $set('time', null);
                            }),

                        Select::make('professional_id')
                            ->label('Profissional')
                            ->options(fn (Get $get) => $get('service_id')
                                ? Professional::where('active', true)
                                    ->whereHas('services', fn ($q) => $q->whereKey($get('service_id')))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                : [])
                            ->disabled(fn (Get $get) => ! $get('service_id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('time', null)),

                        DatePicker::make('date')
                            ->label('Dia')
                            ->default(today())
                            ->minDate(today())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('time', null)),

                        Select::make('time')
                            ->label('Horário')
                            ->options(fn (Get $get) => self::slotOptions($get))
                            ->disabled(fn (Get $get) => ! $get('professional_id') || ! $get('date'))
                            ->helperText(fn (Get $get) => $get('professional_id') && $get('date') && empty(self::slotOptions($get))
                                ? 'Nenhum horário livre nesse dia.'
                                : null)
                            ->required(),
                    ]),

                Section::make('Cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('customer_phone')
                            ->label('Telefone')
                            ->tel()
                            ->required()
                            ->rule(fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                $digits = strlen(preg_replace('/\D+/', '', (string) $value));
                                if ($digits < 10 || $digits > 13) {
                                    $fail('Informe um telefone válido com DDD.');
                                }
                            }),

                        TextInput::make('customer_email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data, Action $action, BookingService $booking) {
                try {
                    $booking->book(
                        Professional::findOrFail($data['professional_id']),
                        Service::findOrFail($data['service_id']),
                        Carbon::parse($data['date'].' '.$data['time']),
                        [
                            'customer_name' => $data['customer_name'],
                            'customer_phone' => preg_replace('/\D+/', '', $data['customer_phone']),
                            'customer_email' => $data['customer_email'] ?? null,
                        ],
                    );
                } catch (SlotUnavailableException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Agendamento criado.')->success()->send();
            });
    }

    /** @return array<string, string> horário => horário, já sem os ocupados e os passados */
    private static function slotOptions(Get $get): array
    {
        $professional = Professional::find($get('professional_id'));
        $service = Service::find($get('service_id'));

        if (! $professional || ! $service || ! $get('date')) {
            return [];
        }

        return app(AvailabilityService::class)
            ->slotsFor($professional, $service, $get('date'))
            ->mapWithKeys(fn (Carbon $slot) => [$slot->format('H:i') => $slot->format('H:i')])
            ->all();
    }
}
