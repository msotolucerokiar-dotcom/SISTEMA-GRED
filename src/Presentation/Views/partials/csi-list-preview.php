<?php
declare(strict_types=1);
$actionLabels = require __DIR__ . '/history-action-labels.php';
$previewPriority = mb_strtolower((string) ($selectedRequest['priority_name'] ?? ''));
$previewFinished = $selectedRequest !== null && in_array($selectedRequest['status_code'], ['resolved', 'closed', 'cancelled'], true);
$previewUrl = $selectedRequest === null ? '' : '/csi/requests/' . rawurlencode($selectedRequest['public_code']);
$previewDate = static fn (string $value): string => (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y H:i');
?>
<aside id="detalle-solicitud" class="csi-list-preview" aria-labelledby="detalle-solicitud-titulo">
  <div class="csi-list-preview__heading"><h2 id="detalle-solicitud-titulo"><?= in_array($previewPriority, ['urgente', 'alta'], true) && !$previewFinished ? 'Detalle de solicitud prioritaria' : 'Detalle de solicitud' ?></h2></div>
  <?php if ($selectedRequest === null): ?>
    <p class="csi-list-preview__empty">Selecciona una solicitud para consultar sus datos.</p>
  <?php else: ?>
    <div class="csi-list-preview__body">
      <span class="csi-list-preview__code"><?= $e($selectedRequest['public_code']) ?></span>
      <h3><?= $e($selectedRequest['title']) ?></h3>
      <dl class="csi-list-preview__facts">
        <div><dt>Área solicitante</dt><dd><?= $e($selectedRequest['area_name']) ?></dd></div>
        <div><dt>Solicitante</dt><dd><?= $e($selectedRequest['requester_name']) ?></dd></div>
        <div><dt>Clasificación</dt><dd><?= $e($selectedRequest['category_name'] ?? 'Sin clasificar') ?></dd></div>
        <div><dt>Prioridad</dt><dd><span class="csi-list-preview__tag csi-list-preview__tag--<?= in_array($previewPriority, ['urgente', 'alta'], true) ? 'urgent' : 'neutral' ?>"><?= $e($selectedRequest['priority_name'] ?? 'Sin prioridad') ?></span></dd></div>
        <div><dt>Estado</dt><dd><span class="csi-list-preview__tag csi-list-preview__tag--<?= $e($selectedRequest['status_code']) ?>"><?= $e($selectedRequest['status_name']) ?></span></dd></div>
        <div><dt>Responsable</dt><dd><?= $selectedRequest['technician_name'] === null ? 'Sin asignar' : $e(\App\Presentation\DisplayName::withoutDemoMarker($selectedRequest['technician_name'])) ?></dd></div>
        <div><dt>Fecha de registro</dt><dd><time datetime="<?= $e($selectedRequest['created_at']) ?>"><?= $e($previewDate($selectedRequest['created_at'])) ?></time></dd></div>
      </dl>
      <div class="csi-list-preview__description"><strong>Descripción</strong><p><?= $e($selectedRequest['description']) ?></p></div>
      <div class="csi-list-preview__actions">
        <?php if (!$previewFinished && $user->can('request.assign')): ?><a href="<?= $e($previewUrl) ?>#asignar-responsable" class="csi-list-preview__action--primary"><?= $selectedRequest['technician_name'] === null ? 'Asignar solicitud' : 'Reasignar solicitud' ?></a><?php endif; ?>
        <?php if (!$previewFinished && $user->can('request.classify')): ?><a href="<?= $e($previewUrl) ?>#clasificar-solicitud">Clasificar</a><a href="<?= $e($previewUrl) ?>#priorizar-solicitud">Priorizar</a><?php endif; ?>
        <a href="<?= $e($previewUrl) ?>">Ver detalle completo</a>
      </div>
      <div class="csi-list-preview__history"><h4>Historial de la solicitud</h4>
        <?php if ($selectedHistory === []): ?><p>Aún no hay movimientos.</p><?php else: ?><ol><?php foreach (array_reverse($selectedHistory) as $event): ?><li><strong><?= $e($actionLabels[$event['action']] ?? 'Acción registrada') ?></strong><time datetime="<?= $e($event['created_at']) ?>"><?= $e($previewDate($event['created_at'])) ?></time><small><?= $e(\App\Presentation\DisplayName::withoutDemoMarker($event['actor_name'])) ?></small></li><?php endforeach; ?></ol><?php endif; ?>
        <a href="<?= $e($previewUrl) ?>#historial">Ver historial completo</a>
      </div>
    </div>
  <?php endif; ?>
</aside>
