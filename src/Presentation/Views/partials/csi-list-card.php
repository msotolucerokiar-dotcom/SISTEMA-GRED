<?php
declare(strict_types=1);
$sortBase = $query;
unset($sortBase['sort'], $sortBase['page']);
$sortUrl = static function (string $column) use ($sortBase): string {
    $current = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'recent';
    $next = $current === $column . '_asc' ? $column . '_desc' : $column . '_asc';
    return '/csi/requests?' . http_build_query([...$sortBase, 'sort' => $next]);
};
$dateParts = static function (string $value): array {
    $date = (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Lima'));
    return [$date->format(DATE_ATOM), $date->format('d/m/Y'), $date->format('H:i')];
};
$paginationQuery = $query;
unset($paginationQuery['page'], $paginationQuery['per_page']);
$perPageUrl = '/csi/requests' . ($paginationQuery === [] ? '' : '?' . http_build_query($paginationQuery));
?>
<section id="listado-solicitudes" class="csi-list-card" aria-label="Listado de solicitudes">
<div class="csi-list-card__heading"><div><h2>Listado de solicitudes</h2><p>Consulta los casos y accede a su expediente para realizar la gesti&oacute;n correspondiente.</p></div><span><?= (int) $listing['total'] ?> resultados</span></div>
<?php if ($listing['items'] === []): ?><div class="csi-list-empty"><h2>No hay solicitudes para mostrar</h2><p>Prueba con otros filtros o espera nuevas solicitudes.</p></div><?php else: ?>
<div class="csi-list-table-wrap"><table class="csi-list-table csi-list-table--compact"><caption class="sr-only">Solicitudes filtradas</caption><thead><tr>
<th scope="col"><a href="<?= $e($sortUrl('code')) ?>">Código ↕</a></th>
<th scope="col">Oficina</th>
<th scope="col">Problema</th>
<th scope="col">Prioridad</th>
<th scope="col">Estado</th>
<th scope="col">Responsable</th>
<th scope="col"><a href="<?= $e($sortUrl('created')) ?>">Registro ↓</a></th>
<th scope="col">Acciones</th></tr></thead><tbody>
<?php foreach ($listing['items'] as $item):
    $code = $item['status_code'] ?? '';
    $priority = mb_strtolower((string) ($item['priority_name'] ?? ''));
    $priorityClass = str_contains($priority, 'urgent') ? 'urgent' : (str_contains($priority, 'alta') ? 'high' : (str_contains($priority, 'baja') ? 'low' : 'normal'));
    $detailUrl = '/csi/requests/' . rawurlencode($item['public_code']);
    [$createdIso, $createdDate, $createdTime] = $dateParts($item['created_at']);
?>
<tr class="csi-list-row csi-list-row--<?= $priorityClass ?>">
<td data-label="Código"><a class="csi-list-code" href="<?= $e($detailUrl) ?>"><?= $e($item['public_code']) ?></a></td>
<td data-label="Oficina"><span class="csi-list-area"><?= $e($item['area_name']) ?></span></td>
<td data-label="Problema"><span class="csi-list-problem"><?= $e($item['title']) ?></span></td>
<td data-label="Prioridad"><span class="csi-list-badge csi-list-badge--<?= $priorityClass ?>"><?= $e($item['priority_name'] ?? 'Sin definir') ?></span></td>
<td data-label="Estado"><span class="csi-list-badge csi-list-badge--<?= $e($code) ?>"><?= $e($item['status_name']) ?></span></td>
<td data-label="Responsable"><span class="csi-list-assignee"><?= $item['technician_name'] === null ? '<em>Sin asignar</em>' : $e(\App\Presentation\DisplayName::withoutDemoMarker($item['technician_name'])) ?></span></td>
<td data-label="Registro"><time class="csi-list-stamp" datetime="<?= $e($createdIso) ?>"><?= $e($createdDate) ?><small><?= $e($createdTime) ?></small></time></td>
<td data-label="Acciones"><div class="csi-list-actions"><a href="<?= $e($detailUrl) ?>" class="csi-list-icon" aria-label="Ver solicitud <?= $e($item['public_code']) ?>" title="Ver detalle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></a><?php if (!in_array($code, ['resolved', 'closed', 'cancelled'], true) && ($user->can('request.assign') || $user->can('request.classify'))): ?><details class="csi-list-more"><summary aria-label="Más acciones para <?= $e($item['public_code']) ?>" title="Más acciones">⋮</summary><div><?php if ($user->can('request.assign')): ?><a href="<?= $e($detailUrl) ?>#asignar-responsable"><?= $item['technician_name'] === null ? 'Asignar' : 'Reasignar' ?></a><?php endif; ?><?php if ($user->can('request.classify')): ?><a href="<?= $e($detailUrl) ?>#clasificar-solicitud">Clasificar</a><a href="<?= $e($detailUrl) ?>#priorizar-solicitud">Priorizar</a><?php endif; ?></div></details><?php endif; ?></div></td>
</tr>
<?php endforeach; ?></tbody></table></div>
<nav class="csi-list-pagination" aria-label="Páginas de solicitudes"><span>Mostrando <?= (($listing['page'] - 1) * $listing['per_page']) + 1 ?>–<?= min($listing['page'] * $listing['per_page'], $listing['total']) ?> de <?= (int) $listing['total'] ?> solicitudes</span><div class="csi-list-pagination__controls"><form method="get" action="/csi/requests"><label for="per_page">Filas por página:</label><?php foreach ($paginationQuery as $key => $value): if (is_string($value)): ?><input type="hidden" name="<?= $e($key) ?>" value="<?= $e($value) ?>"><?php endif; endforeach; ?><select id="per_page" name="per_page" onchange="this.form.requestSubmit()"><?php foreach ([5,10,20] as $size): ?><option value="<?= $size ?>" <?= $listing['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select><button class="sr-only focus:not-sr-only" type="submit">Aplicar filas por página</button></form><div class="csi-list-pagination__pages"><?php if ($listing['page'] > 1): ?><a href="<?= $e($pageUrl(1)) ?>" aria-label="Primera página">«</a><a href="<?= $e($pageUrl($listing['page'] - 1)) ?>" aria-label="Página anterior">‹</a><?php endif; ?><?php for ($number = max(1, $listing['page'] - 2); $number <= min($listing['pages'], $listing['page'] + 2); $number++): ?><?php if ($number === $listing['page']): ?><strong aria-current="page"><?= $number ?></strong><?php else: ?><a href="<?= $e($pageUrl($number)) ?>" aria-label="Página <?= $number ?>"><?= $number ?></a><?php endif; ?><?php endfor; ?><?php if ($listing['page'] < $listing['pages']): ?><a href="<?= $e($pageUrl($listing['page'] + 1)) ?>" aria-label="Página siguiente">›</a><a href="<?= $e($pageUrl($listing['pages'])) ?>" aria-label="Última página">»</a><?php endif; ?></div></div></nav>
<?php endif; ?>
</section>
