<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\TaskRepository;
use PDO;
use Throwable;

final class PdoTaskRepository implements TaskRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function interns(): array
    {
        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']],
            $this->pdo->query("SELECT u.id, u.full_name AS name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code = 'intern' AND u.is_active = TRUE ORDER BY u.full_name")->fetchAll());
    }

    public function listForIntern(int $internId): array
    {
        $query = $this->pdo->prepare('SELECT t.id, t.instructions, t.state, t.created_at, r.public_code, r.title, s.name AS status_name FROM request_tasks t JOIN requests r ON r.id = t.request_id JOIN statuses s ON s.id = r.status_id WHERE t.intern_id = :intern ORDER BY t.created_at DESC, t.id DESC');
        $query->execute(['intern' => $internId]);
        return $query->fetchAll();
    }

    public function findForIntern(int $taskId, int $internId): ?array
    {
        $query = $this->pdo->prepare('SELECT t.id, t.instructions, t.work_note, t.state, t.created_at, t.submitted_at, t.approved_at, r.public_code, r.title, r.description, s.name AS status_name, responsible.full_name AS responsible_name FROM request_tasks t JOIN requests r ON r.id = t.request_id JOIN statuses s ON s.id = r.status_id JOIN users responsible ON responsible.id = t.responsible_id WHERE t.id = :id AND t.intern_id = :intern');
        $query->execute(['id' => $taskId, 'intern' => $internId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function attachmentsForIntern(int $taskId, int $internId): array
    {
        $query = $this->pdo->prepare("SELECT att.id, att.original_name, att.size_bytes FROM request_attachments att JOIN request_tasks t ON t.request_id = att.request_id WHERE t.id = :task AND t.intern_id = :intern AND t.state <> 'cancelled' ORDER BY att.id");
        $query->execute(['task' => $taskId, 'intern' => $internId]);
        return $query->fetchAll();
    }

    public function attachmentForIntern(int $attachmentId, int $internId): ?array
    {
        $query = $this->pdo->prepare("SELECT att.storage_key, att.original_name, att.mime_type, att.size_bytes FROM request_attachments att JOIN request_tasks t ON t.request_id = att.request_id WHERE att.id = :id AND t.intern_id = :intern AND t.state <> 'cancelled' ORDER BY t.id DESC LIMIT 1");
        $query->execute(['id' => $attachmentId, 'intern' => $internId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    public function tasksForRequest(string $code): array
    {
        $query = $this->pdo->prepare('SELECT t.id, t.instructions, t.work_note, t.state, t.created_at, t.submitted_at, t.approved_at, intern.full_name AS intern_name, responsible.full_name AS responsible_name, validator.full_name AS validator_name FROM request_tasks t JOIN requests r ON r.id = t.request_id JOIN users intern ON intern.id = t.intern_id JOIN users responsible ON responsible.id = t.responsible_id LEFT JOIN users validator ON validator.id = t.approved_by WHERE r.public_code = :code ORDER BY t.id DESC');
        $query->execute(['code' => $code]);
        return $query->fetchAll();
    }

    public function delegate(string $code, int $actorId, int $internId, string $instructions, bool $manager): bool
    {
        $this->pdo->beginTransaction();
        try {
            $request = $this->pdo->prepare('SELECT r.id, s.code AS status_code FROM requests r JOIN statuses s ON s.id = r.status_id WHERE r.public_code = :code FOR UPDATE OF r');
            $request->execute(['code' => $code]);
            $row = $request->fetch();
            if ($row === false || !in_array($row['status_code'], ['pending', 'in_progress'], true)) { $this->pdo->rollBack(); return false; }
            $assignment = $this->pdo->prepare('SELECT technician_id FROM request_assignments WHERE request_id = :request AND ended_at IS NULL');
            $assignment->execute(['request' => $row['id']]);
            $responsibleId = $assignment->fetchColumn();
            if ($responsibleId === false || (!$manager && (int) $responsibleId !== $actorId)) { $this->pdo->rollBack(); return false; }
            $intern = $this->pdo->prepare("SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id AND u.is_active = TRUE AND r.code = 'intern' FOR SHARE OF u");
            $intern->execute(['id' => $internId]);
            if ($intern->fetchColumn() === false) { $this->pdo->rollBack(); return false; }
            $existing = $this->pdo->prepare("SELECT 1 FROM request_tasks WHERE request_id = :request AND intern_id = :intern AND state IN ('assigned', 'submitted')");
            $existing->execute(['request' => $row['id'], 'intern' => $internId]);
            if ($existing->fetchColumn() !== false) { $this->pdo->rollBack(); return false; }
            $insert = $this->pdo->prepare('INSERT INTO request_tasks (request_id, intern_id, responsible_id, delegated_by, instructions) VALUES (:request, :intern, :responsible, :actor, :instructions) RETURNING id');
            $insert->execute(['request' => $row['id'], 'intern' => $internId, 'responsible' => $responsibleId, 'actor' => $actorId, 'instructions' => $instructions]);
            $taskId = (int) $insert->fetchColumn();
            $this->history((int) $row['id'], $actorId, 'task_delegated', ['task_id' => $taskId, 'intern_id' => $internId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function submit(int $taskId, int $internId, string $note): bool
    {
        $this->pdo->beginTransaction();
        try {
            $requestId = $this->taskRequestId($taskId);
            if ($requestId === null) { $this->pdo->rollBack(); return false; }
            $this->lockRequest($requestId);
            $task = $this->pdo->prepare("SELECT state, intern_id FROM request_tasks WHERE id = :id FOR UPDATE");
            $task->execute(['id' => $taskId]);
            $row = $task->fetch();
            if ($row === false || $row['state'] !== 'assigned' || (int) $row['intern_id'] !== $internId) { $this->pdo->rollBack(); return false; }
            $update = $this->pdo->prepare("UPDATE request_tasks SET work_note = :note, state = 'submitted', submitted_at = NOW() WHERE id = :id");
            $update->execute(['note' => $note, 'id' => $taskId]);
            $this->history($requestId, $internId, 'task_submitted', ['task_id' => $taskId, 'observation' => $note]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function approve(int $taskId, int $actorId, bool $manager): bool
    {
        $this->pdo->beginTransaction();
        try {
            $requestId = $this->taskRequestId($taskId);
            if ($requestId === null) { $this->pdo->rollBack(); return false; }
            $this->lockRequest($requestId);
            $task = $this->pdo->prepare('SELECT state, responsible_id FROM request_tasks WHERE id = :id FOR UPDATE');
            $task->execute(['id' => $taskId]);
            $row = $task->fetch();
            $assignment = $this->pdo->prepare('SELECT technician_id FROM request_assignments WHERE request_id = :id AND ended_at IS NULL');
            $assignment->execute(['id' => $requestId]);
            $currentResponsible = $assignment->fetchColumn();
            if ($row === false || $row['state'] !== 'submitted' || $currentResponsible === false || (int) $row['responsible_id'] !== (int) $currentResponsible || (!$manager && (int) $currentResponsible !== $actorId)) {
                $this->pdo->rollBack();
                return false;
            }
            $update = $this->pdo->prepare("UPDATE request_tasks SET state = 'approved', approved_at = NOW(), approved_by = :actor WHERE id = :id");
            $update->execute(['actor' => $actorId, 'id' => $taskId]);
            $this->history($requestId, $actorId, 'task_approved', ['task_id' => $taskId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function cancel(int $taskId, int $actorId, bool $manager): bool
    {
        $this->pdo->beginTransaction();
        try {
            $requestId = $this->taskRequestId($taskId);
            if ($requestId === null) { $this->pdo->rollBack(); return false; }
            $this->lockRequest($requestId);
            $task = $this->pdo->prepare('SELECT state, responsible_id FROM request_tasks WHERE id = :id FOR UPDATE');
            $task->execute(['id' => $taskId]);
            $row = $task->fetch();
            $assignment = $this->pdo->prepare('SELECT technician_id FROM request_assignments WHERE request_id = :id AND ended_at IS NULL');
            $assignment->execute(['id' => $requestId]);
            $currentResponsible = $assignment->fetchColumn();
            if ($row === false || !in_array($row['state'], ['assigned', 'submitted'], true) || $currentResponsible === false || (int) $row['responsible_id'] !== (int) $currentResponsible || (!$manager && (int) $currentResponsible !== $actorId)) {
                $this->pdo->rollBack(); return false;
            }
            $update = $this->pdo->prepare("UPDATE request_tasks SET state = 'cancelled', cancelled_at = NOW() WHERE id = :id");
            $update->execute(['id' => $taskId]);
            $this->history($requestId, $actorId, 'task_cancelled', ['task_id' => $taskId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    private function taskRequestId(int $taskId): ?int
    {
        $query = $this->pdo->prepare('SELECT request_id FROM request_tasks WHERE id = :id');
        $query->execute(['id' => $taskId]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function lockRequest(int $requestId): void
    {
        $query = $this->pdo->prepare('SELECT id FROM requests WHERE id = :id FOR UPDATE');
        $query->execute(['id' => $requestId]);
        $query->fetchColumn();
    }

    /** @param array<string,int|string> $details */
    private function history(int $requestId, int $actorId, string $action, array $details): void
    {
        $query = $this->pdo->prepare('INSERT INTO request_history (request_id, actor_id, action, details) VALUES (:request, :actor, :action, CAST(:details AS JSONB))');
        $query->execute(['request' => $requestId, 'actor' => $actorId, 'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR)]);
    }
}
