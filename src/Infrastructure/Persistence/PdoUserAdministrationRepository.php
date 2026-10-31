<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Authentication\UserAdministrationRepository;
use PDO;
use PDOException;
use Throwable;

final class PdoUserAdministrationRepository implements UserAdministrationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listUsers(int $limit, int $offset): array
    {
        $query = $this->pdo->prepare('SELECT u.id, u.email, u.full_name, u.is_active, r.code AS role_code, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC LIMIT :limit OFFSET :offset');
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = in_array($row['is_active'], [true, 1, '1', 't', 'true'], true);
            return $row;
        }, $query->fetchAll());
    }

    public function countUsers(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function createUser(string $email, string $fullName, string $passwordHash, string $roleCode, bool $active): bool
    {
        try {
            $query = $this->pdo->prepare('INSERT INTO users (email, full_name, password_hash, role_id, is_active, must_change_password) SELECT :email, :full_name, :password_hash, id, :active, TRUE FROM roles WHERE code = :role RETURNING id');
            $query->execute(['email' => $email, 'full_name' => $fullName, 'password_hash' => $passwordHash, 'role' => $roleCode, 'active' => $active ? 'true' : 'false']);
            return $query->fetchColumn() !== false;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') { return false; }
            throw $exception;
        }
    }

    public function updateAccess(int $actorId, int $userId, string $roleCode, bool $active): string
    {
        $this->pdo->beginTransaction();
        try {
            // Serializa cambios concurrentes que podrían retirar el último responsable.
            $this->pdo->query("SELECT id FROM roles WHERE code = 'csi_manager' FOR UPDATE")->fetchColumn();
            $target = $this->pdo->prepare('SELECT u.id, u.is_active, r.code AS role_code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id FOR UPDATE OF u');
            $target->execute(['id' => $userId]);
            $row = $target->fetch();
            if ($row === false) {
                $this->pdo->rollBack();
                return 'not_found';
            }
            if ($row['role_code'] === 'csi_manager' && in_array($row['is_active'], [true, 1, '1', 't', 'true'], true)
                && ($roleCode !== 'csi_manager' || !$active)) {
                $count = (int) $this->pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'csi_manager' AND u.is_active = TRUE")->fetchColumn();
                if ($count <= 1) {
                    $this->pdo->rollBack();
                    return 'last_manager';
                }
            }
            $update = $this->pdo->prepare('UPDATE users SET role_id = (SELECT id FROM roles WHERE code = :role), is_active = :active, updated_at = NOW() WHERE id = :id');
            $update->execute(['role' => $roleCode, 'active' => $active ? 'true' : 'false', 'id' => $userId]);
            if ($row['role_code'] === 'technician' && ($roleCode !== 'technician' || !$active)) {
                $audit = $this->pdo->prepare("INSERT INTO user_permission_history (user_id, permission_id, actor_id, action) SELECT user_id, permission_id, :actor, 'revoked' FROM user_permissions WHERE user_id = :user");
                $audit->execute(['actor' => $actorId, 'user' => $userId]);
                $remove = $this->pdo->prepare('DELETE FROM user_permissions WHERE user_id = :user');
                $remove->execute(['user' => $userId]);
            }
            $this->pdo->commit();
            return 'updated';
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }
}
