<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\ManagementRepository;

final class GetRequestForManagement
{
    public function __construct(private readonly ManagementRepository $requests) {}

    /** @return array{request:array<string,mixed>,attachments:list<array<string,mixed>>}|null */
    public function execute(string $code): ?array
    {
        $request = $this->requests->find($code);
        return $request === null ? null : ['request' => $request, 'attachments' => $this->requests->attachments($code)];
    }
}
