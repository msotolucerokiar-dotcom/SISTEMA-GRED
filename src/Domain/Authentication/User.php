<?php

declare(strict_types=1);

namespace App\Domain\Authentication;

final readonly class User
{
    /** @param list<string> $permissions */
    public function __construct(
        public int $id,
        public string $email,
        public string $fullName,
        public string $roleCode,
        public string $roleName,
        public array $permissions,
        public bool $mustChangePassword = false
    ) {
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
