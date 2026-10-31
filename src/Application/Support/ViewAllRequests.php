<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\ManagementRepository;

final class ViewAllRequests
{
    public function __construct(private readonly ManagementRepository $requests) {}

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function execute(int $page): array
    {
        $total = $this->requests->countAll();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $page), $pages);
        return ['items' => $this->requests->listAll(10, ($page - 1) * 10), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
