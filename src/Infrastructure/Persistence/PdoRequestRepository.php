<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\RequestRepository;
use PDO;
use RuntimeException;
use Throwable;

final class PdoRequestRepository implements RequestRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $requesterId, int $areaId, string $title, string $description, array $attachments): string
    {
        $this->pdo->beginTransaction();
        try {
            $sequence = (int) $this->pdo->query("SELECT nextval('request_public_number_seq')")->fetchColumn();
            $code = sprintf('SOL-%s-%06d', gmdate('Y'), $sequence);
            $statement = $this->pdo->prepare(
                "INSERT INTO requests (public_code, requester_id, area_id, status_id, title, description)
                 VALUES (:code, :requester, :area, (SELECT id FROM statuses WHERE code = 'pending'), :title, :description) RETURNING id"
            );
            $statement->execute(['code' => $code, 'requester' => $requesterId, 'area' => $areaId, 'title' => $title, 'description' => $description]);
            $id = (int) $statement->fetchColumn();
            $insert = $this->pdo->prepare(
                'INSERT INTO request_attachments (request_id, uploaded_by, storage_key, original_name, mime_type, size_bytes)
                 VALUES (:request, :user, :storage_key, :name, :mime, :size)'
            );
            foreach ($attachments as $attachment) {
                $insert->execute([
                    'request' => $id, 'user' => $requesterId, 'storage_key' => $attachment['key'],
                    'name' => $attachment['name'], 'mime' => $attachment['mime'], 'size' => $attachment['size'],
                ]);
            }
            $history = $this->pdo->prepare("INSERT INTO request_history (request_id, actor_id, action) VALUES (:request, :actor, 'created')");
            $history->execute(['request' => $id, 'actor' => $requesterId]);
            $this->pdo->commit();
            return $code;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function listOwn(int $requesterId, int $limit, int $offset): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.created_at, s.name AS status_name, s.code AS status_code, a.name AS area_name
             FROM requests r JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             WHERE r.requester_id = :user ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('user', $requesterId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function countOwn(int $requesterId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM requests WHERE requester_id = :user');
        $statement->execute(['user' => $requesterId]);
        return (int) $statement->fetchColumn();
    }

    public function findOwn(string $code, int $requesterId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.public_code, r.title, r.description, r.created_at, r.updated_at, s.name AS status_name,
                    s.code AS status_code, a.name AS area_name
             FROM requests r JOIN statuses s ON s.id = r.status_id JOIN areas a ON a.id = r.area_id
             WHERE r.public_code = :code AND r.requester_id = :user'
        );
        $statement->execute(['code' => $code, 'user' => $requesterId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function attachmentsForOwn(string $code, int $requesterId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ra.id, ra.original_name, ra.mime_type, ra.size_bytes
             FROM request_attachments ra JOIN requests r ON r.id = ra.request_id
             WHERE r.public_code = :code AND r.requester_id = :user ORDER BY ra.id'
        );
        $statement->execute(['code' => $code, 'user' => $requesterId]);
        return $statement->fetchAll();
    }

    public function attachmentForOwn(int $attachmentId, int $requesterId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ra.storage_key, ra.original_name, ra.mime_type, ra.size_bytes
             FROM request_attachments ra JOIN requests r ON r.id = ra.request_id
             WHERE ra.id = :id AND r.requester_id = :user'
        );
        $statement->execute(['id' => $attachmentId, 'user' => $requesterId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function activeAreas(): array
    {
        $rows = $this->pdo->query('SELECT id, name FROM areas WHERE is_active = TRUE ORDER BY name')->fetchAll();
        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']], $rows);
    }
}
