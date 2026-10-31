<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Support\InsightsRepository;

final class SearchRequests
{
    public function __construct(private readonly InsightsRepository $insights, private readonly RequestFilters $parser) {}

    /** @param array<string,mixed> $input @return array{filters:array<string,mixed>,items:list<array<string,mixed>>,total:int,page:int,pages:int} */
    public function execute(array $input, int $page): array
    {
        $filters = $this->parser->fromInput($input);
        $total = $this->insights->countFiltered($filters);
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $page), $pages);
        return ['filters' => $filters, 'items' => $this->insights->listFiltered($filters, 10, ($page - 1) * 10), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
