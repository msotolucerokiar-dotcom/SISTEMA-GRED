<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\InsightsRepository;
use PDO;

final class PdoInsightsRepository implements InsightsRepository
{
    private const LIST_COLUMNS = 'r.public_code, r.title, r.created_at, s.name AS status_name, s.code AS status_code,
        a.name AS area_name, u.full_name AS requester_name, c.name AS category_name,
        p.name AS priority_name, tech.full_name AS technician_name';

    private const JOINS = ' FROM requests r JOIN statuses s ON s.id = r.status_id
        JOIN areas a ON a.id = r.area_id JOIN users u ON u.id = r.requester_id
        LEFT JOIN categories c ON c.id = r.category_id LEFT JOIN priorities p ON p.id = r.priority_id
        LEFT JOIN request_assignments ra ON ra.request_id = r.id AND ra.ended_at IS NULL
        LEFT JOIN users tech ON tech.id = ra.technician_id';

    public function __construct(private readonly PDO $pdo) {}

    public function historyForManager(string $code): array { return $this->history($code, null, null); }

    public function historyForWorker(string $code, int $workerId): array { return $this->history($code, 'worker', $workerId); }

    public function historyForTechnician(string $code, int $technicianId): array { return $this->history($code, 'technician', $technicianId); }

    /** @return list<array<string,mixed>> */
    private function history(string $code, ?string $scope, ?int $userId): array
    {
        $join = $scope === 'technician'
            ? ' JOIN request_assignments ra_scope ON ra_scope.request_id = r.id AND ra_scope.technician_id = :user_id AND ra_scope.ended_at IS NULL'
            : '';
        $where = $scope === 'worker' ? ' AND r.requester_id = :user_id' : '';
        $query = $this->pdo->prepare(
            'SELECT h.action, h.details, h.created_at, actor.full_name AS actor_name
             FROM request_history h JOIN requests r ON r.id = h.request_id
             JOIN users actor ON actor.id = h.actor_id' . $join .
            ' WHERE r.public_code = :code' . $where . ' ORDER BY h.created_at ASC, h.id ASC'
        );
        $params = ['code' => $code];
        if ($scope !== null) { $params['user_id'] = $userId; }
        $query->execute($params);
        return array_map(static function (array $row): array {
            $row['details'] = json_decode($row['details'], true, 512, JSON_THROW_ON_ERROR);
            return $row;
        }, $query->fetchAll());
    }

    public function countFiltered(array $filters): int
    {
        [$where, $params] = $this->where($filters);
        $query = $this->pdo->prepare('SELECT COUNT(*)' . self::JOINS . $where);
        $query->execute($params);
        return (int) $query->fetchColumn();
    }

    public function listFiltered(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->where($filters);
        $query = $this->pdo->prepare('SELECT ' . self::LIST_COLUMNS . self::JOINS . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT :limit OFFSET :offset');
        $this->bind($query, $params);
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->bindValue('offset', $offset, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countByStatus(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $query = $this->pdo->prepare('SELECT s.code, s.name, COUNT(*) AS total' . self::JOINS . $where . ' GROUP BY s.id ORDER BY s.id');
        $query->execute($params);
        return array_map(static fn (array $row): array => ['code' => $row['code'], 'name' => $row['name'], 'total' => (int) $row['total']], $query->fetchAll());
    }

    public function exportFiltered(array $filters): iterable
    {
        [$where, $params] = $this->where($filters);
        $query = $this->pdo->prepare('SELECT ' . self::LIST_COLUMNS . self::JOINS . $where . ' ORDER BY r.created_at DESC, r.id DESC');
        $query->execute($params);
        while (($row = $query->fetch()) !== false) { yield $row; }
    }

    public function filterOptions(): array
    {
        return [
            'statuses' => $this->options('SELECT id, name FROM statuses ORDER BY id'),
            'areas' => $this->options('SELECT id, name FROM areas WHERE is_active = TRUE ORDER BY name'),
            'categories' => $this->options('SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name'),
            'technicians' => $this->options("SELECT u.id, u.full_name AS name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code IN ('technician', 'csi_manager') AND u.is_active = TRUE ORDER BY u.full_name"),
        ];
    }

    /** @return list<array{id:int,name:string}> */
    private function options(string $sql): array
    {
        return array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']], $this->pdo->query($sql)->fetchAll());
    }

    /** @param array<string,mixed> $filters @return array{string,array<string,mixed>} */
    private function where(array $filters): array
    {
        $clauses = [];
        $params = [];
        if ($filters['search'] !== '') {
            $clauses[] = '(r.public_code ILIKE :search OR r.title ILIKE :title_search)';
            $params['search'] = '%' . $this->escapeLike($filters['search']) . '%';
            $params['title_search'] = $params['search'];
        }
        foreach (['status' => 'r.status_id', 'area' => 'r.area_id', 'category' => 'r.category_id', 'technician' => 'ra.technician_id'] as $key => $column) {
            if ($filters[$key] !== null) { $clauses[] = $column . ' = :' . $key; $params[$key] = $filters[$key]; }
        }
        if ($filters['from'] !== null) { $clauses[] = "r.created_at >= (CAST(:from_date AS DATE) AT TIME ZONE 'America/Lima')"; $params['from_date'] = $filters['from']; }
        if ($filters['to'] !== null) { $clauses[] = "r.created_at < ((CAST(:to_date AS DATE) + INTERVAL '1 day') AT TIME ZONE 'America/Lima')"; $params['to_date'] = $filters['to']; }
        return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** @param array<string,mixed> $params */
    private function bind(\PDOStatement $query, array $params): void
    {
        foreach ($params as $key => $value) { $query->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR); }
    }
}
