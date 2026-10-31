<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\MigrationRunner;
use App\Infrastructure\Persistence\PdoAssignedRequestRepository;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoAssignedRequestRepositoryTest extends TestCase
{
    public function testAssignmentProgressSolutionAndReassignmentPrivacy(): void
    {
        $container = ContainerFactory::build();
        $container->get(MigrationRunner::class)->migrate(dirname(__DIR__, 3) . '/database/migrations', dirname(__DIR__, 3) . '/database/seeds');
        $pdo = $container->get(PDO::class);
        $requests = $container->get(PdoRequestRepository::class);
        $management = $container->get(PdoManagementRepository::class);
        $assigned = $container->get(PdoAssignedRequestRepository::class);
        $area = $requests->activeAreas()[0]['id'];
        $users = [];
        $code = null;
        try {
            foreach (['worker', 'worker', 'csi_manager', 'technician', 'technician'] as $role) {
                $statement = $pdo->prepare("INSERT INTO users (role_id, area_id, email, full_name, password_hash) VALUES ((SELECT id FROM roles WHERE code = :role), :area, :email, :name, 'test-hash') RETURNING id");
                $statement->execute(['role' => $role, 'area' => $area, 'email' => 'assigned-' . bin2hex(random_bytes(8)) . '@example.test', 'name' => 'Test ' . $role]);
                $users[] = (int) $statement->fetchColumn();
            }
            $code = $requests->create($users[0], $area, 'Impresora sin conexión', 'No imprime documentos.', [[
                'key' => bin2hex(random_bytes(16)), 'name' => 'evidencia.pdf', 'mime' => 'application/pdf', 'size' => 128,
            ]]);
            self::assertTrue($management->assign($code, $users[3], $users[2]));
            self::assertSame(1, $assigned->countAssigned($users[3]));
            self::assertNotNull($assigned->findAssigned($code, $users[3]));
            $attachmentId = (int) $assigned->attachments($code, $users[3])[0]['id'];
            self::assertNotNull($assigned->attachment($attachmentId, $users[3]));
            self::assertNull($assigned->attachment($attachmentId, $users[4]));
            self::assertCount(1, $assigned->listAssigned($users[3], 10, 0));
            self::assertNull($assigned->findAssigned($code, $users[4]));
            self::assertFalse($assigned->updateProgress($code, $users[4], 'in_progress', 'Sin permiso', null));
            self::assertFalse($assigned->updateProgress($code, $users[3], 'resolved', 'Salto inválido', 'Solución'));
            self::assertTrue($assigned->updateProgress($code, $users[3], 'in_progress', 'Revisé los cables.', null));
            self::assertNull($assigned->solution($code, $users[0]));
            self::assertTrue($assigned->updateProgress($code, $users[3], 'resolved', 'Probé una página.', 'Se reconectó y probó la impresora.'));
            self::assertSame('Se reconectó y probó la impresora.', $assigned->solution($code, $users[0])['solution']);
            self::assertNull($assigned->solution($code, $users[1]));
            self::assertFalse($assigned->updateProgress($code, $users[3], 'resolved', 'Duplicado', 'Otra solución'));
            self::assertTrue($management->assign($code, $users[4], $users[2]));
            self::assertNull($assigned->findAssigned($code, $users[3]));
            self::assertNull($assigned->attachment($attachmentId, $users[3]));
            self::assertNotNull($assigned->attachment($attachmentId, $users[4]));
            self::assertFalse($assigned->updateProgress($code, $users[3], 'in_progress', 'Revocado', null));
            self::assertNotNull($assigned->findAssigned($code, $users[4]));
            $history = $pdo->prepare('SELECT action, details FROM request_history WHERE request_id = (SELECT id FROM requests WHERE public_code = :code) ORDER BY id');
            $history->execute(['code' => $code]);
            $actions = $history->fetchAll();
            self::assertSame(['created', 'assigned', 'started', 'resolved', 'reassigned'], array_column($actions, 'action'));
            self::assertSame('Revisé los cables.', json_decode($actions[2]['details'], true, 512, JSON_THROW_ON_ERROR)['observation']);
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
