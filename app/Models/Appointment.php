<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
        'service_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'starts_at',
        'ends_at',
        'status',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Token único para cancelar/reagendar por link, sem login.
        static::creating(function (Appointment $appointment) {
            $appointment->token ??= Str::random(40);
        });
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Antecedência mínima para o próprio cliente cancelar pelo link. */
    public const CUSTOMER_CANCEL_NOTICE_HOURS = 2;

    /** Em cima da hora, o cancelamento passa a ser só pelo estabelecimento. */
    public function customerCanCancel(): bool
    {
        return $this->canTransitionTo(AppointmentStatus::Cancelled)
            && now()->addHours(self::CUSTOMER_CANCEL_NOTICE_HOURS)->lte($this->starts_at);
    }

    public function canTransitionTo(AppointmentStatus $status): bool
    {
        if (! in_array($status, $this->status->allowedTransitions(), true)) {
            return false;
        }

        return ! $status->requiresStarted() || $this->starts_at->isPast();
    }

    public function transitionTo(AppointmentStatus $status): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new \DomainException(
                "Não é possível mudar de {$this->status->label()} para {$status->label()}."
            );
        }

        $this->update(['status' => $status]);
    }

    /** Agendamentos que ocupam horário (pendente ou confirmado). */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', AppointmentStatus::blocking());
    }

    /** Agendamentos que se sobrepõem ao intervalo informado. */
    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }
}
