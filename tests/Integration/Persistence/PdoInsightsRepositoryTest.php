<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Application\Support\RequestFilters;
use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoInsightsRepository;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoInsightsRepositoryTest extends TestCase
{
    public function testCombinedFiltersCountsExportsAndHistoryScopes(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $repo = $container->get(PdoInsightsRepository::class);
        $requests = $container->get(PdoRequestRepository::class);
        $management = $container->get(PdoManagementRepository::class);
        $area = $requests->activeAreas()[0]['id'];
        $users = [];
        $code = null;
        try {
            foreach (['worker', 'worker', 'csi_manager', 'technician', 'technician'] as $role) {
                $statement = $pdo->prepare("INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = :role), :area, :email, :name, 'test-hash') RETURNING id");
                $statement->execute(['role' => $role, 'area' => $area, 'email' => 'insights-' . bin2hex(random_bytes(8)) . '@example.test', 'name' => 'Test ' . $role]);
                $users[] = (int) $statement->fetchColumn();
            }
            $title = 'Unique-' . bin2hex(random_bytes(8));
            $code = $requests->create($users[0], $area, $title, 'Sin conexión.', []);
            $category = $management->categories()[0]['id'];
            $priority = $management->priorities()[0]['id'];
            self::assertTrue($management->classify($code, $category, $priority, $users[2]));
            self::assertTrue($management->assign($code, $users[3], $users[2]));
            $status = (int) $pdo->query("SELECT id FROM statuses WHERE code = 'pending'")->fetchColumn();
            $filters = (new RequestFilters())->fromInput(['search' => $title, 'status' => (string) $status, 'area' => (string) $area, 'category' => (string) $category, 'technician' => (string) $users[3]]);
            self::assertSame(1, $repo->countFiltered($filters));
            self::assertSame($code, $repo->listFiltered($filters, 10, 0)[0]['public_code']);
            self::assertSame($code, iterator_to_array($repo->exportFiltered($filters))[0]['public_code']);
            self::assertSame(1, $repo->countByStatus($filters)[0]['total']);
            self::assertCount(3, $repo->historyForManager($code));
            self::assertCount(3, $repo->historyForWorker($code, $users[0]));
            self::assertSame([], $repo->historyForWorker($code, $users[1]));
            self::assertCount(3, $repo->historyForTechnician($code, $users[3]));
            self::assertSame([], $repo->historyForTechnician($code, $users[4]));
            self::assertTrue($management->assign($code, $users[4], $users[2]));
            self::assertSame([], $repo->historyForTechnician($code, $users[3]));
            self::assertCount(4, $repo->historyForTechnician($code, $users[4]));
            self::assertSame(0, $repo->countFiltered($filters));
            self::assertNotEmpty($repo->filterOptions()['statuses']);
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
