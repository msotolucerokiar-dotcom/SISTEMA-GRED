<?php
/** @var array $notificationRecent */
$notificationUnread = $notificationUnread ?? 0;
$notificationRecent = $notificationRecent ?? [];
?>
<details class="notification-bell">
  <summary aria-label="Notificaciones, <?= (int) $notificationUnread ?> sin leer" title="Notificaciones">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>
    <?php if ($notificationUnread > 0): ?><span class="notification-bell__count" aria-hidden="true"><?= $notificationUnread > 99 ? '99+' : (int) $notificationUnread ?></span><?php endif; ?>
  </summary>
  <div class="notification-bell__menu">
    <div class="notification-bell__heading"><strong>Notificaciones</strong><span><?= (int) $notificationUnread ?> sin leer</span></div>
    <?php if ($notificationRecent === []): ?><p class="notification-bell__empty">Aún no tienes notificaciones.</p><?php endif; ?>
    <?php foreach ($notificationRecent as $notification): ?>
      <form action="/notifications/open" method="post" class="notification-bell__item <?= $notification['read_at'] === null ? 'notification-bell__item--unread' : '' ?>">
        <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
        <button type="submit"><strong><?= $e($notification['title']) ?></strong><span><?= $e($notification['message']) ?></span><small><?= $e((new DateTimeImmutable($notification['created_at']))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y H:i')) ?></small></button>
      </form>
    <?php endforeach; ?>
    <a class="notification-bell__all" href="/notifications">Ver todas las notificaciones</a>
  </div>
</details>
<script src="/assets/notifications-bell.js?v=<?= (int) filemtime(dirname(__DIR__, 4) . '/public/assets/notifications-bell.js') ?>" defer></script>
