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

    public function attentionNeeded(int $limit): array
    {
        $query = $this->pdo->prepare('SELECT ' . self::LIST_COLUMNS . ', r.category_id, r.priority_id, ra.technician_id' . self::JOINS .
            ' WHERE s.is_terminal = FALSE AND (ra.request_id IS NULL OR r.category_id IS NULL OR r.priority_id IS NULL)
              ORDER BY r.created_at ASC, r.id ASC LIMIT :limit');
        $query->bindValue('limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function technicianWorkload(): array
    {
        $summary = $this->pdo->query("SELECT u.id, u.full_name AS name, role.code AS role_code,
                COUNT(r.id) FILTER (WHERE s.is_terminal = FALSE) AS open_count,
                COUNT(r.id) FILTER (WHERE s.code = 'pending') AS pending_count,
                COUNT(r.id) FILTER (WHERE s.code = 'in_progress') AS in_progress_count
            FROM users u JOIN roles role ON role.id = u.role_id
            LEFT JOIN request_assignments ra ON ra.technician_id = u.id AND ra.ended_at IS NULL
            LEFT JOIN requests r ON r.id = ra.request_id
            LEFT JOIN statuses s ON s.id = r.status_id
            WHERE u.is_active = TRUE AND role.code IN ('technician', 'csi_manager')
            GROUP BY u.id, role.code
            ORDER BY open_count DESC, u.full_name ASC")->fetchAll();
        $cases = $this->pdo->query('SELECT technician_id, public_code, title, status_name FROM (
                SELECT ra.technician_id, r.public_code, r.title, s.name AS status_name,
                       ROW_NUMBER() OVER (PARTITION BY ra.technician_id ORDER BY r.created_at ASC, r.id ASC) AS position
                FROM request_assignments ra JOIN requests r ON r.id = ra.request_id
                JOIN statuses s ON s.id = r.status_id
                WHERE ra.ended_at IS NULL AND s.is_terminal = FALSE
            ) ranked WHERE position <= 3 ORDER BY technician_id, position')->fetchAll();
        $casesByTechnician = [];
        foreach ($cases as $case) {
            $casesByTechnician[(int) $case['technician_id']][] = [
                'public_code' => $case['public_code'], 'title' => $case['title'], 'status_name' => $case['status_name'],
            ];
        }
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'], 'name' => $row['name'], 'role_code' => $row['role_code'],
            'open_count' => (int) $row['open_count'], 'pending_count' => (int) $row['pending_count'],
            'in_progress_count' => (int) $row['in_progress_count'],
            'cases' => $casesByTechnician[(int) $row['id']] ?? [],
        ], $summary);
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
            'statuses' => $this->options('SELECT id, code, name FROM statuses ORDER BY id'),
            'areas' => $this->options('SELECT id, name FROM areas WHERE is_active = TRUE ORDER BY name'),
            'categories' => $this->options('SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name'),
            'technicians' => $this->options("SELECT u.id, u.full_name AS name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.code IN ('technician', 'csi_manager') AND u.is_active = TRUE ORDER BY u.full_name"),
        ];
    }

    /** @return list<array{id:int,name:string,code?:string}> */
    private function options(string $sql): array
    {
        return array_map(static function (array $row): array {
            $option = ['id' => (int) $row['id'], 'name' => $row['name']];
            if (isset($row['code'])) { $option['code'] = $row['code']; }
            return $option;
        }, $this->pdo->query($sql)->fetchAll());
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
        if (($filters['assignment'] ?? null) === 'unassigned') { $clauses[] = 'ra.request_id IS NULL'; }
        if (($filters['assignment'] ?? null) === 'assigned') { $clauses[] = 'ra.request_id IS NOT NULL'; }
        if (($filters['attention'] ?? null) === 'needed') { $clauses[] = 's.is_terminal = FALSE AND (ra.request_id IS NULL OR r.category_id IS NULL OR r.priority_id IS NULL)'; }
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
