<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoAssignedRequestRepository;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use App\Infrastructure\Persistence\PdoTaskRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoTaskRepositoryTest extends TestCase
{
    public function testFinishedRequestRejectsPendingTaskActions(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $requests = $container->get(PdoRequestRepository::class);
        $management = $container->get(PdoManagementRepository::class);
        $tasks = $container->get(PdoTaskRepository::class);
        $ids = [];
        $code = null;
        try {
            foreach (['worker', 'csi_manager', 'technician', 'intern'] as $role) {
                $ids[] = $users->create('terminal-task-' . bin2hex(random_bytes(8)) . '@example.test', 'Task ' . $role, 'test-hash', $role);
            }
            $code = $requests->create($ids[0], $requests->activeAreas()[0]['id'], 'Caso heredado', 'Tarea aún abierta.', []);
            self::assertTrue($management->assign($code, $ids[2], $ids[1]));
            self::assertTrue($tasks->delegate($code, $ids[2], $ids[3], 'Revisar equipo.', false));
            $taskId = (int) $tasks->listForIntern($ids[3])[0]['id'];
            $pdo->prepare("UPDATE requests SET status_id = (SELECT id FROM statuses WHERE code = 'resolved') WHERE public_code = :code")
                ->execute(['code' => $code]);

            self::assertFalse($tasks->submit($taskId, $ids[3], 'Trabajo realizado.'));
            self::assertFalse($tasks->approve($taskId, $ids[2], false));
            self::assertFalse($tasks->cancel($taskId, $ids[2], false));
            self::assertSame('assigned', $tasks->findForIntern($taskId, $ids[3])['state']);
        } finally {
            if ($code !== null) {
                $pdo->prepare('DELETE FROM requests WHERE public_code = :code')->execute(['code' => $code]);
            }
            foreach ($ids as $id) {
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
            }
        }
    }

    public function testDelegationValidationAndReassignmentKeepPrincipalAssignment(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $requests = $container->get(PdoRequestRepository::class);
        $management = $container->get(PdoManagementRepository::class);
        $assigned = $container->get(PdoAssignedRequestRepository::class);
        $tasks = $container->get(PdoTaskRepository::class);
        $ids = [];
        $codes = [];
        try {
            foreach (['worker', 'csi_manager', 'technician', 'intern', 'intern'] as $role) {
                $ids[] = $users->create('task-' . bin2hex(random_bytes(8)) . '@example.test', 'Task ' . $role, 'test-hash', $role);
            }
            $area = $requests->activeAreas()[0]['id'];
            $codes[] = $requests->create($ids[0], $area, 'Impresora bloqueada', 'La impresora no responde.', [[
                'key' => bin2hex(random_bytes(24)), 'name' => 'foto.png', 'mime' => 'image/png', 'size' => 100,
            ]]);
            $code = $codes[0];
            self::assertTrue($management->assign($code, $ids[2], $ids[1]));
            self::assertTrue($tasks->delegate($code, $ids[2], $ids[3], 'Revisar el cableado de la impresora.', false));
            self::assertTrue($tasks->delegate($code, $ids[2], $ids[4], 'Comprobar el controlador de impresión.', false));
            self::assertFalse($tasks->delegate($code, $ids[4], $ids[4], 'No autorizado.', false));
            self::assertFalse($tasks->delegate($code, $ids[2], $ids[3], 'Duplicado.', false));
            $taskId = (int) $tasks->listForIntern($ids[3])[0]['id'];
            $secondTaskId = (int) $tasks->listForIntern($ids[4])[0]['id'];
            self::assertNull($tasks->findForIntern($taskId, $ids[4]));
            $attachmentId = (int) $tasks->attachmentsForIntern($taskId, $ids[3])[0]['id'];
            self::assertNotNull($tasks->attachmentForIntern($attachmentId, $ids[3]));
            self::assertSame($ids[2], (int) $management->find($code)['technician_id']);
            self::assertTrue($assigned->updateProgress($code, $ids[2], 'in_progress', 'Inicio de atención.', null));
            self::assertFalse($assigned->updateProgress($code, $ids[2], 'resolved', 'Falta tarea.', 'Solución anticipada.'));
            self::assertTrue($tasks->submit($taskId, $ids[3], 'Se revisó el cableado y se reconectó.'));
            self::assertFalse($tasks->approve($taskId, $ids[4], false));
            self::assertTrue($tasks->approve($taskId, $ids[2], false));
            self::assertSame('approved', $tasks->findForIntern($taskId, $ids[3])['state']);
            self::assertFalse($assigned->updateProgress($code, $ids[2], 'resolved', 'Otra tarea pendiente.', 'No todavía.'));
            self::assertTrue($tasks->submit($secondTaskId, $ids[4], 'Controlador instalado y verificado.'));
            self::assertTrue($tasks->approve($secondTaskId, $ids[1], true));
            self::assertTrue($assigned->updateProgress($code, $ids[2], 'resolved', 'Trabajo verificado.', 'La impresora vuelve a funcionar.'));
            self::assertSame($ids[2], (int) $management->find($code)['technician_id']);

            $codes[] = $requests->create($ids[0], $area, 'Red lenta', 'La red está muy lenta.', []);
            $code2 = $codes[1];
            self::assertTrue($management->assign($code2, $ids[2], $ids[1]));
            self::assertTrue($tasks->delegate($code2, $ids[1], $ids[3], 'Medir la conexión de red.', true));
            $cancelledId = (int) $tasks->listForIntern($ids[3])[0]['id'];
            self::assertTrue($management->assign($code2, $ids[1], $ids[1]));
            self::assertSame('cancelled', $tasks->findForIntern($cancelledId, $ids[3])['state']);
            self::assertFalse($tasks->submit($cancelledId, $ids[3], 'Trabajo tardío.'));
            self::assertTrue($assigned->updateProgress($code2, $ids[1], 'in_progress', 'Ingeniero atiende directamente.', null));

            $codes[] = $requests->create($ids[0], $area, 'Equipo sin audio', 'No funciona el audio.', []);
            $code3 = $codes[2];
            self::assertTrue($management->assign($code3, $ids[2], $ids[1]));
            self::assertTrue($tasks->delegate($code3, $ids[2], $ids[3], 'Revisar salida de audio.', false));
            $manualCancelId = (int) $tasks->listForIntern($ids[3])[0]['id'];
            self::assertFalse($tasks->cancel($manualCancelId, $ids[4], false));
            self::assertTrue($tasks->cancel($manualCancelId, $ids[2], false));
            self::assertFalse($tasks->submit($manualCancelId, $ids[3], 'Se revisó el audio.'));
        } finally {
            foreach ($codes as $code) {
                $delete = $pdo->prepare('DELETE FROM requests WHERE public_code = :code');
                $delete->execute(['code' => $code]);
            }
            foreach ($ids as $id) {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $delete->execute(['id' => $id]);
            }
        }
    }
}
