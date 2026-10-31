<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\AssignedRequestRepository;

final class GetAssignedRequest
{
    public function __construct(private readonly AssignedRequestRepository $requests) {}

    /** @return array{request:array<string,mixed>,attachments:list<array<string,mixed>>}|null */
    public function execute(int $technicianId, string $code): ?array
    {
        $request = $this->requests->findAssigned($code, $technicianId);
        return $request === null ? null : ['request' => $request, 'attachments' => $this->requests->attachments($code, $technicianId)];
    }
}
