<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\InsightsRepository;

final class GetRequestHistory
{
    public function __construct(private readonly InsightsRepository $insights) {}

    /** @return list<array<string,mixed>> */
    public function forManager(string $code): array { return $this->insights->historyForManager($code); }

    /** @return list<array<string,mixed>> */
    public function forWorker(string $code, int $workerId): array { return $this->insights->historyForWorker($code, $workerId); }

    /** @return list<array<string,mixed>> */
    public function forTechnician(string $code, int $technicianId): array { return $this->insights->historyForTechnician($code, $technicianId); }
}
