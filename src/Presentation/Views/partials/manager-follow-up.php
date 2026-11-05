<?php
declare(strict_types=1);

$zone = new DateTimeZone('America/Lima');
$today = (new DateTimeImmutable('now', $zone))->format('Y-m-d');
$yesterday = (new DateTimeImmutable('yesterday', $zone))->format('Y-m-d');
$activityLabels = [
    'created' => ['Nueva solicitud registrada', 'Pendiente', 'new'],
    'assigned' => ['Solicitud asignada', 'Asignada', 'assigned'],
    'reassigned' => ['Solicitud reasignada', 'Reasignada', 'assigned'],
    'classified' => ['Solicitud clasificada', 'Clasificada', 'classified'],
    'started' => ['Atención iniciada', 'En atención', 'progress'],
    'resolved' => ['Solicitud solucionada', 'Solucionada', 'resolved'],
    'task_delegated' => ['Tarea delegada', 'Delegada', 'assigned'],
    'task_submitted' => ['Tarea enviada a validar', 'Por validar', 'progress'],
    'task_approved' => ['Tarea validada', 'Validada', 'resolved'],
    'task_cancelled' => ['Tarea cancelada', 'Cancelada', 'cancelled'],
    'tasks_cancelled' => ['Tareas canceladas por reasignación', 'Canceladas', 'cancelled'],
];
$activityDescription = static function (array $item): string {
    $actor = \App\Presentation\DisplayName::withoutDemoMarker($item['actor_name']);
    $technician = $item['technician_name'] === null ? 'el personal técnico' : \App\Presentation\DisplayName::withoutDemoMarker($item['technician_name']);
    $intern = $item['intern_name'] === null ? 'un practicante' : \App\Presentation\DisplayName::withoutDemoMarker($item['intern_name']);
    return match ($item['action']) {
        'created' => 'Registrada por ' . \App\Presentation\DisplayName::withoutDemoMarker($item['requester_name']) . ' · ' . $item['area_name'],
        'assigned' => 'Asignada a ' . $technician . ' por ' . $actor,
        'reassigned' => 'De ' . ($item['previous_name'] === null ? 'responsable anterior' : \App\Presentation\DisplayName::withoutDemoMarker($item['previous_name'])) . ' a ' . $technician,
        'classified' => 'Clasificación actualizada por ' . $actor,
        'started' => $actor . ' comenzó la atención',
        'resolved' => 'Solución registrada por ' . $actor,
        'task_delegated' => 'Tarea asignada a ' . $intern,
        'task_submitted' => $intern . ' envió su trabajo para validación',
        'task_approved' => 'Trabajo validado por ' . $actor,
        'task_cancelled' => 'Tarea cancelada por ' . $actor,
        'tasks_cancelled' => 'Tareas pendientes cerradas al cambiar el responsable',
        default => 'Movimiento registrado por ' . $actor,
    };
};
?>
<section class="manager-followup rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="manager-follow-up">
  <div class="manager-activity__heading"><div class="manager-activity__title"><?php if (!empty($activityFullPage)): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="m5 13 4-4 3 5 3-3 4 3"/></svg><?php endif; ?><div><h2 id="manager-follow-up"><?= !empty($activityFullPage) ? 'Historial de actividad' : 'Actividad reciente' ?></h2><p><?= !empty($activityFullPage) ? 'Últimos movimientos de solicitudes del CSI' : 'Últimos movimientos de las solicitudes y tareas' ?></p></div></div><?php if (!empty($activityFullPage)): ?><span class="manager-activity__period">Todos</span><?php else: ?><a class="manager-activity__all" href="/csi/activity">Historial completo <span aria-hidden="true">→</span></a><?php endif; ?></div>
  <?php if ($managerActivity === []): ?><p class="manager-activity__empty">Todavía no hay movimientos registrados.</p><?php else: ?>
  <ol class="manager-activity__list">
    <?php foreach (!empty($activityFullPage) ? $managerActivity : array_slice($managerActivity, 0, 2) as $item): ?>
      <?php
        [$title, $badge, $style] = $activityLabels[$item['action']] ?? ['Actividad registrada', 'Actualizada', 'classified'];
        $eventAt = (new DateTimeImmutable($item['event_at']))->setTimezone($zone);
        $date = $eventAt->format('Y-m-d');
        $timeLabel = $date === $today ? $eventAt->format('H:i') : ($date === $yesterday ? 'Ayer, ' . $eventAt->format('H:i') : $eventAt->format('d/m/Y, H:i'));
      ?>
      <li class="manager-activity__item manager-activity__item--<?= $e($style) ?>"><span class="manager-activity__icon" aria-hidden="true"><?php if ($style === 'new'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg><?php elseif ($style === 'assigned'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-4 2-6 6-6 2 0 4 1 5 3M16 16h6m-3-3v6"/></svg><?php elseif ($style === 'progress'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 4-8 9 4 1-1 6 9-10-4-1 1-5"/></svg><?php elseif ($style === 'resolved'): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 5 5 9-10"/></svg><?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg><?php endif; ?></span><div class="manager-activity__content"><div class="manager-activity__line"><h3><?= $e($title) ?></h3><time datetime="<?= $e($eventAt->format(DATE_ATOM)) ?>"><?= $e($timeLabel) ?></time></div><p><a href="/csi/requests/<?= rawurlencode($item['public_code']) ?><?= str_starts_with($item['action'], 'task_') ? '#tareas-colaboracion' : '' ?>"><?= $e($item['public_code']) ?></a> · <?= $e($activityDescription($item)) ?></p><?php if (!empty($activityFullPage)): ?><span class="manager-activity__badge"><?= $e($badge) ?></span><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>
  <?php if (!empty($activityFullPage)): ?><div class="manager-activity__footer"><a href="/dashboard#manager-follow-up">Volver al panel <span aria-hidden="true">→</span></a></div><?php else: ?><div class="manager-activity__validation"><div><strong><?= (int) $managerPendingValidation ?> <?= $managerPendingValidation === 1 ? 'tarea espera' : 'tareas esperan' ?> validación</strong><p>Revisa el trabajo antes de cerrar los casos.</p></div><a href="/csi/requests?validation=pending">Revisar tareas <span aria-hidden="true">→</span></a></div><?php endif; ?>
</section>
