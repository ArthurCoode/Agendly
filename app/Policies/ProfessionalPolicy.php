<?php

namespace App\Policies;

use App\Models\Professional;
use App\Models\User;

class ProfessionalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Professional $professional): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Professional $professional): bool
    {
        return true;
    }

    /** Excluir apagaria em cascata todos os agendamentos dele: só sem histórico. */
    public function delete(User $user, Professional $professional): bool
    {
        return ! $professional->appointments()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
