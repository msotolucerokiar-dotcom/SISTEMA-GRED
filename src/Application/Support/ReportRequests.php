<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\InsightsRepository;

final class ReportRequests
{
    public function __construct(private readonly InsightsRepository $insights, private readonly RequestFilters $parser) {}

    /** @param array<string,mixed> $input @return array{filters:array<string,mixed>,total:int,statuses:list<array{code:string,name:string,total:int}>} */
    public function summary(array $input): array
    {
        $filters = $this->parser->fromInput($input, true);
        return ['filters' => $filters, 'total' => $this->insights->countFiltered($filters), 'statuses' => $this->insights->countByStatus($filters)];
    }

    /** @param array<string,mixed> $filters @return iterable<array<string,mixed>> */
    public function rows(array $filters): iterable { return $this->insights->exportFiltered($filters); }
}
