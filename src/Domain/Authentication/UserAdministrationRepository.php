<?php

declare(strict_types=1);

namespace App\Domain\Authentication;

interface UserAdministrationRepository
{
    /** @return list<array{id:int,email:string,full_name:string,role_code:string,role_name:string,is_active:bool}> */
    public function listUsers(int $limit, int $offset): array;

    public function countUsers(): int;

    /** @return 'updated'|'not_found'|'last_manager' */
    public function updateAccess(int $userId, string $roleCode, bool $active): string;
}
