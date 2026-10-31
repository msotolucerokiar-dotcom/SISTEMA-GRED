<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\RequestRepository;

final class GetOwnRequest
{
    public function __construct(private readonly RequestRepository $requests)
    {
    }

    /** @return array{request:array<string,mixed>,attachments:list<array<string,mixed>>}|null */
    public function execute(int $requesterId, string $code): ?array
    {
        $request = $this->requests->findOwn($code, $requesterId);
        return $request === null ? null : ['request' => $request, 'attachments' => $this->requests->attachmentsForOwn($code, $requesterId)];
    }
}
