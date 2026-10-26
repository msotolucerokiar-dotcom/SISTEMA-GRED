<?php

declare(strict_types=1);

namespace App\Application\Authorization;

use App\Domain\Authentication\User;

final class CanAccess
{
    public function execute(?User $user, string $permission): bool
    {
        return $user !== null && $user->can($permission);
    }
}
