<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * Cria um agendamento de forma atômica.
     *
     * @param  array{customer_name: string, customer_phone: string, customer_email?: ?string}  $customer
     *
     * @throws SlotUnavailableException
     */
    public function book(Professional $professional, Service $service, Carbon $start, array $customer): Appointment
    {
        return DB::transaction(function () use ($professional, $service, $start, $customer) {
            // 1) Trava a linha do profissional. Precisa ser o primeiro comando da transação:
            //    quem chegar depois espera aqui até o commit/rollback de quem chegou antes.
            Professional::whereKey($professional->id)->lockForUpdate()->first();

            // 2) Revalida com a trava ativa. Cobre o caso de alguém ter reservado
            //    o mesmo horário entre a tela de escolha e o clique em "Confirmar".
            if (! $this->availability->isAvailable($professional, $service, $start)) {
                throw new SlotUnavailableException();
            }

            // 3) Só agora grava. O fim é calculado pela duração do serviço.
            return Appointment::create([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'customer_name' => $customer['customer_name'],
                'customer_phone' => $customer['customer_phone'],
                'customer_email' => $customer['customer_email'] ?? null,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($service->duration_minutes),
                'status' => AppointmentStatus::Confirmed,
            ]);
        });
    }
}
