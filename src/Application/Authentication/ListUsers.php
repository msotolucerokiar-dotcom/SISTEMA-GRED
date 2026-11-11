<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Domain\Authentication\UserAdministrationRepository;

final class ListUsers
{
    public function __construct(private readonly UserAdministrationRepository $users) {}

    /** @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function execute(int $page, string $search = '', string $role = '', string $status = '', int $areaId = 0): array
    {
        $total = $this->users->countUsers($search, $role, $status, $areaId);
        $pages = max(1, (int) ceil($total / 20));
        $page = min(max(1, $page), $pages);
        return ['items' => $this->users->listUsers(20, ($page - 1) * 20, $search, $role, $status, $areaId), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
