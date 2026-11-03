<?php
declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\PdoManagementRepository;
use App\Infrastructure\Persistence\PdoNotificationRepository;
use App\Infrastructure\Persistence\PdoRequestRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoNotificationRepositoryTest extends TestCase
{
    public function testNotificationsReachOnlyTheirRecipientsAndCanOnlyBeOpenedByThem(): void
    {
        $container = ContainerFactory::build();
        $pdo = $container->get(PDO::class);
        $users = $container->get(PdoUserRepository::class);
        $requests = $container->get(PdoRequestRepository::class);
        $management = $container->get(PdoManagementRepository::class);
        $notifications = $container->get(PdoNotificationRepository::class);
        $ids = [];
        $code = null;
        try {
            foreach (['worker', 'worker', 'csi_manager', 'technician'] as $role) {
                $ids[] = $users->create('notification-' . bin2hex(random_bytes(8)) . '@example.test', 'Notification ' . $role, 'test-hash', $role);
            }
            $code = $requests->create($ids[0], $requests->activeAreas()[0]['id'], 'Caso de notificaciones', 'Caso de prueba.', []);
            self::assertSame(1, $notifications->unreadCount($ids[2]));
            self::assertSame(0, $notifications->unreadCount($ids[0]));
            self::assertSame(0, $notifications->unreadCount($ids[1]));
            self::assertTrue($management->assign($code, $ids[3], $ids[2]));
            self::assertSame(1, $notifications->unreadCount($ids[3]));
            self::assertSame(1, $notifications->unreadCount($ids[0]));
            self::assertSame(0, $notifications->unreadCount($ids[1]));
            $notificationId = (int) $notifications->listFor($ids[3], 1)[0]['id'];
            self::assertNull($notifications->open($notificationId, $ids[1]));
            self::assertSame(1, $notifications->unreadCount($ids[3]));
            self::assertSame('/technician/requests/' . $code, $notifications->open($notificationId, $ids[3]));
            self::assertSame(0, $notifications->unreadCount($ids[3]));
        } finally {
            if ($code !== null) {
                $pdo->prepare('DELETE FROM requests WHERE public_code = :code')->execute(['code' => $code]);
            }
            foreach ($ids as $id) {
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
            }
        }
    }
}
