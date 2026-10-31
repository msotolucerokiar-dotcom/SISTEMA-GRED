<?php

declare(strict_types=1);

namespace App\Tests\Unit\Authentication;

use App\Application\Authentication\Login;
use App\Domain\Authentication\UserRepository;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    public function testAuthenticatesActiveUserWithValidPassword(): void
    {
        $repository = new InMemoryUserRepository();
        $repository->record = [
            'id' => 7, 'email' => 'person@example.test', 'full_name' => 'Test Person',
            'password_hash' => password_hash('correct horse battery', PASSWORD_ARGON2ID),
            'role_code' => 'worker', 'role_name' => 'Trabajador', 'permissions' => ['request.create'], 'is_active' => true,
        ];

        $result = (new Login($repository))->execute(' PERSON@example.test ', 'correct horse battery', '127.0.0.1');

        self::assertTrue($result->succeeded());
        self::assertSame('worker', $result->user?->roleCode);
        self::assertSame('person@example.test', $repository->lastEmail);
        self::assertSame([true], $repository->attempts);
    }

    public function testRejectsInvalidCredentialsAndRecordsFailure(): void
    {
        $repository = new InMemoryUserRepository();
        $repository->record = [
            'id' => 7, 'email' => 'person@example.test', 'full_name' => 'Test Person',
            'password_hash' => password_hash('correct horse battery', PASSWORD_ARGON2ID),
            'role_code' => 'worker', 'role_name' => 'Trabajador', 'permissions' => [], 'is_active' => true,
        ];

        $result = (new Login($repository))->execute('person@example.test', 'wrong password', '127.0.0.1');

        self::assertFalse($result->succeeded());
        self::assertSame([false], $repository->attempts);
    }

    public function testRejectsLoginWhenAttemptLimitWasAlreadyReached(): void
    {
        $repository = new InMemoryUserRepository();
        $repository->failures = 5;

        $result = (new Login($repository))->execute('person@example.test', 'correct horse battery', '127.0.0.1');

        self::assertFalse($result->succeeded());
        self::assertTrue($result->rateLimited);
        self::assertSame([], $repository->attempts);
    }

    public function testRejectsInactiveAccountEvenWithCorrectPassword(): void
    {
        $repository = new InMemoryUserRepository();
        $repository->record = [
            'id' => 7, 'email' => 'person@example.test', 'full_name' => 'Test Person',
            'password_hash' => password_hash('correct horse battery', PASSWORD_ARGON2ID),
            'role_code' => 'worker', 'role_name' => 'Trabajador', 'permissions' => [], 'is_active' => false,
        ];

        self::assertFalse((new Login($repository))->execute('person@example.test', 'correct horse battery', '127.0.0.1')->succeeded());
        self::assertSame([false], $repository->attempts);
    }
}

final class InMemoryUserRepository implements UserRepository
{
    public ?array $record = null;
    public int $failures = 0;
    public ?string $lastEmail = null;
    /** @var list<bool> */
    public array $attempts = [];

    public function findForAuthentication(string $email): ?array
    {
        $this->lastEmail = $email;
        return $this->record;
    }

    public function findActiveById(int $id): ?\App\Domain\Authentication\User
    {
        return null;
    }

    public function recordAttempt(string $email, string $ipAddress, bool $wasSuccessful): void
    {
        $this->attempts[] = $wasSuccessful;
    }

    public function countRecentFailures(string $email, string $ipAddress, int $windowMinutes): int
    {
        return $this->failures;
    }

    public function create(string $email, string $fullName, string $passwordHash, string $roleCode): int
    {
        return 1;
    }

    public function changeInitialPassword(int $userId, string $currentPassword, string $newPasswordHash): bool
    {
        return false;
    }
}
