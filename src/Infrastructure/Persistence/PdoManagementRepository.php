<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\ManagementRepository;
use PDO;
use Throwable;

final class PdoManagementRepository implements ManagementRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listAll(int $limit, int $offset): array
    {
        $query = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.created_at, s.name AS status_name, a.name AS area_name,
                    u.full_name AS requester_name, c.name AS category_name, p.name AS priority_name,
                    tech.full_name AS technician_name
             FROM requests r JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             JOIN users u ON u.id = r.requester_id LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN priorities p ON p.id = r.priority_id
             LEFT JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
             LEFT JOIN users tech ON tech.id = ra.technician_id
             ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset'
        );
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn();
    }

    public function find(string $code): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.description, r.created_at, r.category_id, r.priority_id,
                    s.name AS status_name, a.name AS area_name, u.full_name AS requester_name,
                    u.email AS requester_email, c.name AS category_name, p.name AS priority_name,
                    tech.id AS technician_id, tech.full_name AS technician_name
             FROM requests r JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             JOIN users u ON u.id = r.requester_id LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN priorities p ON p.id = r.priority_id
             LEFT JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
             LEFT JOIN users tech ON tech.id = ra.technician_id
             WHERE r.public_code = :code'
        );
        $query->execute(['code' => $code]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function attachments(string $code): array
    {
        $query = $this->pdo->prepare(
            'SELECT ra.id, ra.original_name, ra.mime_type, ra.size_bytes
             FROM request_attachments ra JOIN requests r ON r.id = ra.request_id
             WHERE r.public_code = :code ORDER BY ra.id'
        );
        $query->execute(['code' => $code]);
        return $query->fetchAll();
    }

    public function attachment(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT storage_key, original_name, mime_type, size_bytes FROM request_attachments WHERE id = :id');
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function categories(): array
    {
        return $this->options('SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name');
    }

    public function priorities(): array
    {
        return $this->options('SELECT id, name FROM priorities ORDER BY sort_order');
    }

    public function technicians(): array
    {
        return $this->options("SELECT u.id, u.full_name AS name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'technician' AND u.is_active = TRUE ORDER BY u.full_name");
    }

    public function classify(string $code, int $categoryId, int $priorityId, int $actorId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $requestId = $this->lockRequest($code);
            if ($requestId === null) {
                $this->pdo->rollBack();
                return false;
            }
            $update = $this->pdo->prepare('UPDATE requests SET category_id = :category, priority_id = :priority, updated_at = NOW() WHERE id = :id');
            $update->execute(['category' => $categoryId, 'priority' => $priorityId, 'id' => $requestId]);
            $this->history($requestId, $actorId, 'classified', ['category_id' => $categoryId, 'priority_id' => $priorityId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function assign(string $code, int $technicianId, int $actorId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $requestId = $this->lockRequest($code);
            if ($requestId === null) {
                $this->pdo->rollBack();
                return false;
            }
            $technician = $this->pdo->prepare(
                "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE u.id = :id AND u.is_active = TRUE AND r.code = 'technician' FOR SHARE OF u"
            );
            $technician->execute(['id' => $technicianId]);
            if ($technician->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            $previous = $this->pdo->prepare('SELECT technician_id FROM request_assignments WHERE request_id = :id AND ended_at IS NULL');
            $previous->execute(['id' => $requestId]);
            $previousId = $previous->fetchColumn();
            if ($previousId !== false && (int) $previousId === $technicianId) {
                $this->pdo->commit();
                return true;
            }
            $end = $this->pdo->prepare('UPDATE request_assignments SET ended_at = NOW() WHERE request_id = :id AND ended_at IS NULL');
            $end->execute(['id' => $requestId]);
            $insert = $this->pdo->prepare('INSERT INTO request_assignments (request_id, technician_id, assigned_by) VALUES (:request, :technician, :actor)');
            $insert->execute(['request' => $requestId, 'technician' => $technicianId, 'actor' => $actorId]);
            $this->history($requestId, $actorId, $previousId === false ? 'assigned' : 'reassigned', [
                'technician_id' => $technicianId, 'previous_technician_id' => $previousId === false ? null : (int) $previousId,
            ]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    /** @return list<array{id:int,name:string}> */
    private function options(string $sql): array
    {
        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']], $this->pdo->query($sql)->fetchAll());
    }

    private function lockRequest(string $code): ?int
    {
        $query = $this->pdo->prepare('SELECT id FROM requests WHERE public_code = :code FOR UPDATE');
        $query->execute(['code' => $code]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @param array<string,int|null> $details */
    private function history(int $requestId, int $actorId, string $action, array $details): void
    {
        $query = $this->pdo->prepare('INSERT INTO request_history (request_id, actor_id, action, details) VALUES (:request, :actor, :action, CAST(:details AS JSONB))');
        $query->execute(['request' => $requestId, 'actor' => $actorId, 'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR)]);
    }
}
