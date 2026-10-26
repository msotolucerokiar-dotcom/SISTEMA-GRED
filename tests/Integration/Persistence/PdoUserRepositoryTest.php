<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Application\Authentication\Login;
use App\Infrastructure\Persistence\MigrationRunner;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoUserRepositoryTest extends TestCase
{
    public function testCreatesAndFindsUserWithSeededRolePermissions(): void
    {
        $container = ContainerFactory::build();
        $container->get(MigrationRunner::class)->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
        $pdo = $container->get(PDO::class);
        $email = 'integration-' . bin2hex(random_bytes(6)) . '@example.test';
        $repository = $container->get(PdoUserRepository::class);
        $passwordHash = password_hash('integration-test-password', PASSWORD_ARGON2ID);
        $id = $repository->create($email, 'Integration Worker', $passwordHash, 'worker');

        try {
            $user = $repository->findForAuthentication($email);
            self::assertSame($id, $user['id']);
            self::assertContains('request.create', $user['permissions']);
            self::assertNotContains('request.view_all', $user['permissions']);
            $result = $container->get(Login::class)->execute($email, 'integration-test-password', '127.0.0.1');
            self::assertTrue($result->succeeded());
            self::assertSame($id, $result->user?->id);
            $repository->recordAttempt($email, '127.0.0.1', false);
            self::assertSame(1, $repository->countRecentFailures($email, '127.0.0.1', 15));
        } finally {
            $delete = $pdo->prepare('DELETE FROM users WHERE email = :email');
            $delete->execute(['email' => $email]);
        }
    }
}
