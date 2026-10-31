<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\MigrationRunner;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoManagementRepositoryTest extends TestCase
{
    public function testClassifiesAssignsReassignsAndRecordsHistory(): void
    {
        $container = ContainerFactory::build();
        $container->get(MigrationRunner::class)->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
        $pdo = $container->get(PDO::class);
        $repo = $container->get(PdoManagementRepository::class);
        $requests = $container->get(PdoRequestRepository::class);
        $area = $requests->activeAreas()[0]['id'];
        $users = [];
        $code = null;
        try {
            foreach (['worker', 'csi_manager', 'technician', 'technician'] as $role) {
                $statement = $pdo->prepare("INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = :role), :area, :email, :name, 'test-hash') RETURNING id");
                $statement->execute(['role' => $role, 'area' => $area, 'email' => 'management-' . bin2hex(random_bytes(8)) . '@example.test', 'name' => 'Test ' . $role]);
                $users[] = (int) $statement->fetchColumn();
            }
            $code = $requests->create($users[0], $area, 'Falla de Internet', 'Sin acceso a Internet en la oficina.', []);
            self::assertGreaterThanOrEqual(1, $repo->countAll());
            self::assertNotNull($repo->find($code));
            self::assertNotEmpty($repo->listAll(10, 0));
            $category = $repo->categories()[0]['id'];
            $priority = $repo->priorities()[0]['id'];
            self::assertTrue($repo->classify($code, $category, $priority, $users[1]));
            self::assertSame($category, (int) $repo->find($code)['category_id']);
            self::assertTrue($repo->assign($code, $users[2], $users[1]));
            self::assertSame($users[2], (int) $repo->find($code)['technician_id']);
            self::assertTrue($repo->assign($code, $users[3], $users[1]));
            self::assertSame($users[3], (int) $repo->find($code)['technician_id']);
            $history = $pdo->prepare('SELECT action FROM request_history WHERE request_id = (SELECT id FROM requests WHERE public_code = :code) ORDER BY id');
            $history->execute(['code' => $code]);
            self::assertSame(['created', 'classified', 'assigned', 'reassigned'], $history->fetchAll(PDO::FETCH_COLUMN));
            $assignments = $pdo->prepare('SELECT COUNT(*) FROM request_assignments WHERE request_id = (SELECT id FROM requests WHERE public_code = :code) AND ended_at IS NULL');
            $assignments->execute(['code' => $code]);
            self::assertSame(1, (int) $assignments->fetchColumn());
            self::assertFalse($repo->assign('SOL-9999-999999', $users[2], $users[1]));
        } finally {
            if ($code !== null) {
                $statement = $pdo->prepare('DELETE FROM requests WHERE public_code = :code');
                $statement->execute(['code' => $code]);
            }
            foreach ($users as $id) {
                $statement = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $statement->execute(['id' => $id]);
            }
        }
    }
}
