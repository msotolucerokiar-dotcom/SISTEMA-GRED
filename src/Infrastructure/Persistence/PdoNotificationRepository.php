<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

final class PdoNotificationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function unreadCount(int $userId): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE recipient_id = :user AND read_at IS NULL');
        $query->execute(['user' => $userId]);
        return (int) $query->fetchColumn();
    }

    public function totalCount(int $userId): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE recipient_id = :user');
        $query->execute(['user' => $userId]);
        return (int) $query->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function listFor(int $userId, int $limit, int $offset = 0): array
    {
        $query = $this->pdo->prepare('SELECT id, title, message, target_url, created_at, read_at
            FROM notifications WHERE recipient_id = :user ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset');
        $query->bindValue('user', $userId, PDO::PARAM_INT);
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function open(int $notificationId, int $userId): ?string
    {
        $query = $this->pdo->prepare('UPDATE notifications SET read_at = COALESCE(read_at, NOW())
            WHERE id = :id AND recipient_id = :user RETURNING target_url');
        $query->execute(['id' => $notificationId, 'user' => $userId]);
        $url = $query->fetchColumn();
        return $url === false ? null : (string) $url;
    }
}
