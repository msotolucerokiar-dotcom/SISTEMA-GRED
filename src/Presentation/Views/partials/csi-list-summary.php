<?php
declare(strict_types=1);
$metricQuery = array_intersect_key($query, array_flip(['from', 'to']));
$metricUrl = static fn (array $filters): string => '/csi/requests?' . http_build_query([...$metricQuery, ...$filters]);
$urgentId = (int) (array_column($filterOptions['priorities'], 'id', 'code')['urgent'] ?? 0);
$metricCards = [
    ['Total del período', $listMetrics['total'], $metricUrl([]), 'total'],
    ['Urgentes', $listMetrics['urgent'], $metricUrl(['priority' => $urgentId, 'open' => '1']), 'urgent'],
    ['En atención', $listMetrics['in_progress'], $metricUrl(['status' => (int) ($statusIds['in_progress'] ?? 0)]), 'progress'],
    ['Solucionadas', $listMetrics['resolved'], $metricUrl(['status' => (int) ($statusIds['resolved'] ?? 0)]), 'resolved'],
    ['Sin asignar', $listMetrics['unassigned'], $metricUrl(['assignment' => 'unassigned', 'open' => '1']), 'unassigned'],
];
?>
<section class="csi-list-summary" aria-label="Resumen de solicitudes">
<?php foreach ($metricCards as [$label, $count, $url, $style]): ?><a href="<?= $e($url) ?>" class="csi-list-summary__card csi-list-summary__card--<?= $style ?>"><span class="csi-list-summary__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($style === 'urgent'): ?><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/><?php elseif ($style === 'progress'): ?><path d="M7 3h10M7 21h10M8 3c0 5 2 6 4 9-2 3-4 4-4 9m8-18c0 5-2 6-4 9 2 3 4 4 4 9"/><?php elseif ($style === 'resolved'): ?><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/><?php elseif ($style === 'unassigned'): ?><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/><?php else: ?><path d="M5 3h10l4 4v14H5zM14 3v5h5M8 12h8M8 16h8"/><?php endif; ?></svg></span><span><strong><?= (int) $count ?></strong><small><?= $e($label) ?></small></span></a><?php endforeach; ?>
</section>
