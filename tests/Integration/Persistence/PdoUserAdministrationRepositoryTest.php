<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoUserAdministrationRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use App\Infrastructure\Persistence\MigrationRunner;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoUserAdministrationRepositoryTest extends TestCase
{
    public function testCannotRemoveLastActiveManagerInFreshSchema(): void
    {
        $pdo = ContainerFactory::build()->get(PDO::class);
        $schema = 'sprint6_' . bin2hex(random_bytes(6));
        $pdo->exec('CREATE SCHEMA "' . $schema . '"');
        try {
            $pdo->exec('SET search_path TO "' . $schema . '"');
            (new MigrationRunner($pdo))->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
            $users = new PdoUserRepository($pdo);
            $admin = new PdoUserAdministrationRepository($pdo);
            $first = $users->create('first-' . $schema . '@example.test', 'First Manager', 'test-hash', 'csi_manager');
            self::assertSame('last_manager', $admin->updateAccess($first, 'worker', true));
            self::assertSame('last_manager', $admin->updateAccess($first, 'csi_manager', false));
            self::assertSame('csi_manager', $users->findActiveById($first)?->roleCode);
            $users->create('second-' . $schema . '@example.test', 'Second Manager', 'test-hash', 'csi_manager');
            self::assertSame('updated', $admin->updateAccess($first, 'worker', true));
        } finally {
            $pdo->exec('SET search_path TO public');
            $pdo->exec('DROP SCHEMA "' . $schema . '" CASCADE');
        }
    }

    public function testListsAndChangesRoleAndActiveState(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $admin = $container->get(PdoUserAdministrationRepository::class);
        $email = 'access-' . bin2hex(random_bytes(8)) . '@example.test';
        $id = $users->create($email, 'Access Test', password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID), 'worker');
        try {
            self::assertGreaterThanOrEqual(1, $admin->countUsers());
            self::assertContains($id, array_column($admin->listUsers(20, 0), 'id'));
            self::assertSame('updated', $admin->updateAccess($id, 'technician', true));
            self::assertSame('technician', $users->findActiveById($id)?->roleCode);
            self::assertSame('updated', $admin->updateAccess($id, 'technician', false));
            self::assertNull($users->findActiveById($id));
            self::assertSame('not_found', $admin->updateAccess(999999999, 'worker', true));
        } finally {
            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $delete->execute(['id' => $id]);
        }
    }
}
