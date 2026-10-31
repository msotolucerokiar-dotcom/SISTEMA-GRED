<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\RequestRepository;

final class ListOwnRequests
{
    public function __construct(private readonly RequestRepository $requests)
    {
    }

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function execute(int $requesterId, int $page = 1): array
    {
        $page = max(1, $page);
        $total = $this->requests->countOwn($requesterId);
        $pages = max(1, (int) ceil($total / 10));
        $page = min($page, $pages);
        return ['items' => $this->requests->listOwn($requesterId, 10, ($page - 1) * 10), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
