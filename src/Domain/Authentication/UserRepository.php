<?php

declare(strict_types=1);

namespace App\Domain\Authentication;

interface UserRepository
{
    /** @return array{id:int,email:string,full_name:string,password_hash:string,role_code:string,role_name:string,permissions:list<string>,is_active:bool}|null */
    public function findForAuthentication(string $email): ?array;

    public function findActiveById(int $id): ?User;

    public function recordAttempt(string $email, string $ipAddress, bool $wasSuccessful): void;

    public function countRecentFailures(string $email, string $ipAddress, int $windowMinutes): int;

    public function create(string $email, string $fullName, string $passwordHash, string $roleCode): int;
}
