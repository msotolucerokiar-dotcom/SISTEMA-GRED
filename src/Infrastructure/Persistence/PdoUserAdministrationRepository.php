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

    public function listUsers(int $limit, int $offset, string $search = '', string $role = '', string $status = ''): array
    {
        $query = $this->pdo->prepare('SELECT u.id, u.email, u.full_name, u.is_active, r.code AS role_code, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE (:name_search = \'\' OR u.full_name ILIKE :name_pattern OR u.email ILIKE :email_pattern) AND (:role_filter = \'\' OR r.code = :role_value) AND (:status_filter = \'\' OR u.is_active = CAST(:active_value AS boolean)) ORDER BY u.id DESC LIMIT :limit OFFSET :offset');
        $this->bindFilters($query, $search, $role, $status);
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = in_array($row['is_active'], [true, 1, '1', 't', 'true'], true);
            return $row;
        }, $query->fetchAll());
    }

    public function countUsers(string $search = '', string $role = '', string $status = ''): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE (:name_search = \'\' OR u.full_name ILIKE :name_pattern OR u.email ILIKE :email_pattern) AND (:role_filter = \'\' OR r.code = :role_value) AND (:status_filter = \'\' OR u.is_active = CAST(:active_value AS boolean))');
        $this->bindFilters($query, $search, $role, $status);
        $query->execute();
        return (int) $query->fetchColumn();
    }

    private function bindFilters(\PDOStatement $query, string $search, string $role, string $status): void
    {
        $query->bindValue('name_search', $search);
        $query->bindValue('name_pattern', '%' . $search . '%');
        $query->bindValue('email_pattern', '%' . $search . '%');
        $query->bindValue('role_filter', $role);
        $query->bindValue('role_value', $role);
        $query->bindValue('status_filter', $status);
        $query->bindValue('active_value', $status === 'active' ? 'true' : 'false');
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
