<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoUserPermissionRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoUserPermissionRepositoryTest extends TestCase
{
    public function testGrantsAndRevokesIndividualPermissionsWithAudit(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $permissions = $container->get(PdoUserPermissionRepository::class);
        $ids = [];
        try {
            foreach (['csi_manager', 'technician', 'worker'] as $role) {
                $ids[] = $users->create('permission-' . bin2hex(random_bytes(8)) . '@example.test', 'Permission ' . $role, 'test-hash', $role);
            }
            self::assertNotContains('request.assign', $users->findActiveById($ids[1])?->permissions ?? []);
            self::assertSame('changed', $permissions->change($ids[0], $ids[1], 'request.assign', true));
            self::assertSame('unchanged', $permissions->change($ids[0], $ids[1], 'request.assign', true));
            self::assertContains('request.assign', $users->findActiveById($ids[1])?->permissions ?? []);
            self::assertSame(['request.assign'], $permissions->grantsFor($ids[1]));
            self::assertSame('not_technician', $permissions->change($ids[0], $ids[2], 'request.assign', true));
            self::assertSame('changed', $permissions->change($ids[0], $ids[1], 'request.assign', false));
            self::assertNotContains('request.assign', $users->findActiveById($ids[1])?->permissions ?? []);
            self::assertSame(['revoked', 'granted'], array_column($permissions->historyFor($ids[1]), 'action'));
            self::assertContains('user.manage_roles', $users->findActiveById($ids[0])?->permissions ?? []);
        } finally {
            foreach ($ids as $id) {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $delete->execute(['id' => $id]);
            }
        }
    }
}
