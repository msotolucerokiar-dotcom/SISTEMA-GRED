<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Domain\Authentication\User;
use App\Domain\Authentication\UserRepository;

final class Login
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly int $maxAttempts = 5,
        private readonly int $windowMinutes = 15
    ) {
    }

    public function execute(string $email, string $password, string $ipAddress): LoginResult
    {
        $email = mb_strtolower(trim($email));
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 4096) {
            return LoginResult::rejected();
        }

        $record = $this->users->findForAuthentication($email);
        $failures = $this->users->countRecentFailures($email, $ipAddress, $this->windowMinutes);
        if ($failures >= $this->maxAttempts) {
            return LoginResult::rejected(true);
        }

        $passwordHash = $record['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$invalidsalt$invalidhash';
        $validPassword = password_verify($password, $passwordHash);
        $active = $record !== null && $record['is_active'];
        $succeeded = $validPassword && $active;
        $this->users->recordAttempt($email, $ipAddress, $succeeded);

        if (!$succeeded || $record === null) {
            return LoginResult::rejected($failures + 1 >= $this->maxAttempts);
        }

        return LoginResult::authenticated(new User(
            $record['id'], $record['email'], $record['full_name'], $record['role_code'],
            $record['role_name'], $record['permissions'], $record['must_change_password'] ?? false
        ));
    }
}
