<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\AssignedRequestRepository;
use PDO;
use Throwable;

final class PdoAssignedRequestRepository implements AssignedRequestRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listAssigned(int $technicianId, int $limit, int $offset): array
    {
        $query = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.created_at, s.name AS status_name, s.code AS status_code,
                    a.name AS area_name, u.full_name AS requester_name, p.name AS priority_name
             FROM requests r JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
             JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             JOIN users u ON u.id = r.requester_id LEFT JOIN priorities p ON p.id = r.priority_id
             WHERE ra.technician_id = :technician
             ORDER BY r.updated_at DESC, r.id DESC LIMIT :limit OFFSET :offset'
        );
        $query->bindValue('technician', $technicianId, PDO::PARAM_INT);
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countAssigned(int $technicianId): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM request_assignments WHERE technician_id = :id AND ended_at IS NULL');
        $query->execute(['id' => $technicianId]);
        return (int) $query->fetchColumn();
    }

    public function findAssigned(string $code, int $technicianId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.description, r.created_at, r.updated_at,
                    s.name AS status_name, s.code AS status_code, a.name AS area_name,
                    u.full_name AS requester_name, u.email AS requester_email,
                    c.name AS category_name, p.name AS priority_name,
                    sol.solution, sol.created_at AS solved_at
             FROM requests r JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
             JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             JOIN users u ON u.id = r.requester_id LEFT JOIN categories c ON c.id = r.category_id
             LEFT JOIN priorities p ON p.id = r.priority_id
             LEFT JOIN LATERAL (SELECT solution, created_at FROM request_solutions WHERE request_id = r.id ORDER BY id DESC LIMIT 1) sol ON TRUE
             WHERE r.public_code = :code AND ra.technician_id = :technician'
        );
        $query->execute(['code' => $code, 'technician' => $technicianId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function attachments(string $code, int $technicianId): array
    {
        $query = $this->pdo->prepare(
            'SELECT att.id, att.original_name, att.mime_type, att.size_bytes
             FROM request_attachments att JOIN requests r ON r.id = att.request_id
             JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
             WHERE r.public_code = :code AND ra.technician_id = :technician ORDER BY att.id'
        );
        $query->execute(['code' => $code, 'technician' => $technicianId]);
        return $query->fetchAll();
    }

    public function attachment(int $id, int $technicianId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT att.storage_key, att.original_name, att.mime_type, att.size_bytes
             FROM request_attachments att JOIN request_assignments ra ON ra.request_id = att.request_id AND ra.ended_at IS NULL
             WHERE att.id = :id AND ra.technician_id = :technician'
        );
        $query->execute(['id' => $id, 'technician' => $technicianId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function solution(string $code, int $requesterId): ?array
    {
        $query = $this->pdo->prepare(
            "SELECT sol.solution, sol.created_at, u.full_name AS author_name
             FROM request_solutions sol JOIN requests r ON r.id = sol.request_id
             JOIN statuses s ON s.id = r.status_id JOIN users u ON u.id = sol.author_id
             WHERE r.public_code = :code AND r.requester_id = :requester AND s.code IN ('resolved', 'closed')
             ORDER BY sol.id DESC LIMIT 1"
        );
        $query->execute(['code' => $code, 'requester' => $requesterId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function updateProgress(string $code, int $technicianId, string $status, string $observation, ?string $solution): bool
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT id, status_id FROM requests WHERE public_code = :code FOR UPDATE');
            $query->execute(['code' => $code]);
            $request = $query->fetch();
            if ($request === false) {
                $this->pdo->rollBack();
                return false;
            }
            $assignment = $this->pdo->prepare('SELECT 1 FROM request_assignments WHERE request_id = :request AND technician_id = :technician AND ended_at IS NULL');
            $assignment->execute(['request' => $request['id'], 'technician' => $technicianId]);
            if ($assignment->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }
            $current = $this->pdo->prepare('SELECT code FROM statuses WHERE id = :id');
            $current->execute(['id' => $request['status_id']]);
            $currentCode = $current->fetchColumn();
            if (!(($currentCode === 'pending' && $status === 'in_progress') || ($currentCode === 'in_progress' && $status === 'resolved'))) {
                $this->pdo->rollBack();
                return false;
            }
            if ($status === 'resolved') {
                $pendingTasks = $this->pdo->prepare("SELECT COUNT(*) FROM request_tasks WHERE request_id = :request AND state IN ('assigned', 'submitted')");
                $pendingTasks->execute(['request' => $request['id']]);
                if ((int) $pendingTasks->fetchColumn() > 0) {
                    $this->pdo->rollBack();
                    return false;
                }
            }
            $update = $this->pdo->prepare('UPDATE requests SET status_id = (SELECT id FROM statuses WHERE code = :status), updated_at = NOW() WHERE id = :id');
            $update->execute(['status' => $status, 'id' => $request['id']]);
            if ($status === 'resolved') {
                $insert = $this->pdo->prepare('INSERT INTO request_solutions (request_id, author_id, solution) VALUES (:request, :author, :solution)');
                $insert->execute(['request' => $request['id'], 'author' => $technicianId, 'solution' => $solution]);
            }
            $history = $this->pdo->prepare('INSERT INTO request_history (request_id, actor_id, action, details) VALUES (:request, :actor, :action, CAST(:details AS JSONB))');
            $history->execute([
                'request' => $request['id'], 'actor' => $technicianId,
                'action' => $status === 'resolved' ? 'resolved' : 'started',
                'details' => json_encode(['from' => $currentCode, 'to' => $status, 'observation' => $observation], JSON_THROW_ON_ERROR),
            ]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }
}
