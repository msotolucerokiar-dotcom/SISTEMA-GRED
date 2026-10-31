<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Authentication\UserPermissionRepository;
use PDO;
use Throwable;

final class PdoUserPermissionRepository implements UserPermissionRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function grantsFor(int $userId): array
    {
        $query = $this->pdo->prepare('SELECT p.code FROM user_permissions up JOIN permissions p ON p.id = up.permission_id WHERE up.user_id = :user ORDER BY p.code');
        $query->execute(['user' => $userId]);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function historyFor(int $userId): array
    {
        $query = $this->pdo->prepare('SELECT h.action, h.created_at, p.code AS permission_code, actor.full_name AS actor_name FROM user_permission_history h JOIN permissions p ON p.id = h.permission_id LEFT JOIN users actor ON actor.id = h.actor_id WHERE h.user_id = :user ORDER BY h.created_at DESC, h.id DESC LIMIT 30');
        $query->execute(['user' => $userId]);
        return $query->fetchAll();
    }

    public function change(int $actorId, int $userId, string $permissionCode, bool $grant): string
    {
        $this->pdo->beginTransaction();
        try {
            $user = $this->pdo->prepare('SELECT r.code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :user FOR UPDATE OF u');
            $user->execute(['user' => $userId]);
            if ($user->fetchColumn() !== 'technician') {
                $this->pdo->rollBack();
                return 'not_technician';
            }
            $permission = $this->pdo->prepare('SELECT id FROM permissions WHERE code = :code');
            $permission->execute(['code' => $permissionCode]);
            $permissionId = $permission->fetchColumn();
            if ($permissionId === false) {
                $this->pdo->rollBack();
                return 'not_technician';
            }
            if ($grant) {
                $change = $this->pdo->prepare('INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (:user, :permission, :actor) ON CONFLICT DO NOTHING');
                $change->execute(['user' => $userId, 'permission' => $permissionId, 'actor' => $actorId]);
            } else {
                $change = $this->pdo->prepare('DELETE FROM user_permissions WHERE user_id = :user AND permission_id = :permission');
                $change->execute(['user' => $userId, 'permission' => $permissionId]);
            }
            if ($change->rowCount() === 0) {
                $this->pdo->commit();
                return 'unchanged';
            }
            $history = $this->pdo->prepare('INSERT INTO user_permission_history (user_id, permission_id, actor_id, action) VALUES (:user, :permission, :actor, :action)');
            $history->execute(['user' => $userId, 'permission' => $permissionId, 'actor' => $actorId, 'action' => $grant ? 'granted' : 'revoked']);
            $this->pdo->commit();
            return 'changed';
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }
}
