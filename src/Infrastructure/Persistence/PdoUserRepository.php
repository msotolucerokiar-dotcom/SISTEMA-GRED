<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Authentication\UserRepository;
use App\Domain\Authentication\User;
use PDO;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findForAuthentication(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.email, u.full_name, u.password_hash, u.is_active, r.code AS role_code, r.name AS role_name,
                    ARRAY(SELECT p.code FROM role_permission rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = r.id
                          UNION SELECT p.code FROM user_permissions up JOIN permissions p ON p.id = up.permission_id
                          WHERE up.user_id = u.id AND r.code = 'technician') AS permissions
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email"
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $permissions = trim((string) $row['permissions'], '{}');
        $row['permissions'] = $permissions === '' ? [] : str_getcsv($permissions);
        $row['id'] = (int) $row['id'];
        $row['is_active'] = in_array($row['is_active'], [true, 1, '1', 't', 'true'], true);

        return $row;
    }

    public function findActiveById(int $id): ?User
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.email, u.full_name, r.code AS role_code, r.name AS role_name,
                    ARRAY(SELECT p.code FROM role_permission rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = r.id
                          UNION SELECT p.code FROM user_permissions up JOIN permissions p ON p.id = up.permission_id
                          WHERE up.user_id = u.id AND r.code = 'technician') AS permissions
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.is_active = TRUE"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $permissions = trim((string) $row['permissions'], '{}');
        return new User((int) $row['id'], $row['email'], $row['full_name'], $row['role_code'], $row['role_name'],
            $permissions === '' ? [] : str_getcsv($permissions));
    }

    public function recordAttempt(string $email, string $ipAddress, bool $wasSuccessful): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address, was_successful) VALUES (:email, CAST(:ip AS INET), :success)'
        );
        $statement->execute([
            'email' => $email,
            'ip' => $ipAddress,
            'success' => $wasSuccessful ? 'true' : 'false',
        ]);
    }

    public function countRecentFailures(string $email, string $ipAddress, int $windowMinutes): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND ip_address = CAST(:ip AS INET) AND was_successful = FALSE
               AND attempted_at >= NOW() - (:minutes * INTERVAL '1 minute')"
        );
        $statement->execute(['email' => $email, 'ip' => $ipAddress, 'minutes' => $windowMinutes]);

        return (int) $statement->fetchColumn();
    }

    public function create(string $email, string $fullName, string $passwordHash, string $roleCode): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, full_name, password_hash, role_id)
             SELECT :email, :full_name, :password_hash, id FROM roles WHERE code = :role_code RETURNING id'
        );
        $statement->execute([
            'email' => $email,
            'full_name' => $fullName,
            'password_hash' => $passwordHash,
            'role_code' => $roleCode,
        ]);

        return (int) $statement->fetchColumn();
    }
}
