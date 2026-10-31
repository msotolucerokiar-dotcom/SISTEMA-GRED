<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\AssignedRequestRepository;

final class ListAssignedRequests
{
    public function __construct(private readonly AssignedRequestRepository $requests) {}

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function execute(int $technicianId, int $page): array
    {
        $total = $this->requests->countAssigned($technicianId);
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $page), $pages);
        return ['items' => $this->requests->listAssigned($technicianId, 10, ($page - 1) * 10), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
