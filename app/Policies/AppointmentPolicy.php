<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * O painel não usa o create/edit genérico do Filament: criar passa pela
 * BookAppointmentAction (BookingService, com trava contra conflito) e
 * editar horário livremente furaria essa proteção.
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
