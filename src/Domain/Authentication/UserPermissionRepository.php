<?php

declare(strict_types=1);

namespace App\Domain\Authentication;

interface UserPermissionRepository
{
    /** @return list<string> */
    public function grantsFor(int $userId): array;

    /** @return list<array<string,mixed>> */
    public function historyFor(int $userId): array;

    /** @return 'changed'|'unchanged'|'not_technician' */
    public function change(int $actorId, int $userId, string $permissionCode, bool $grant): string;
}
