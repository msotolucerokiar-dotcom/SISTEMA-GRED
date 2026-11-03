<?php
declare(strict_types=1);
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
?>
<!doctype html>
<html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Notificaciones | CSI GRED</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="min-h-screen bg-paper text-ink">
<header class="bg-institutional-800 text-white"><div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-5 py-4"><a href="/dashboard" class="font-bold">CSI · GRED</a><a href="/dashboard" class="rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold hover:bg-white/10">Volver al panel</a></div></header>
<main class="mx-auto max-w-4xl px-5 py-8 sm:py-12"><p class="text-sm font-bold uppercase tracking-wide text-institutional-700">Centro de Sistemas de Información</p><h1 class="mt-2 text-3xl font-extrabold">Notificaciones</h1><p class="mt-2 text-slate-600"><?= (int) $notifications->unreadCount($user->id) ?> sin leer</p>
<?php if ($notificationItems === []): ?><div class="mt-8 rounded-2xl border border-slate-200 bg-white p-8 text-slate-600">Aún no tienes notificaciones.</div><?php endif; ?>
<div class="mt-7 space-y-3"><?php foreach ($notificationItems as $notification): ?>
  <form action="/notifications/open" method="post" class="notification-page__item <?= $notification['read_at'] === null ? 'notification-page__item--unread' : '' ?>"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $notification['id'] ?>"><button type="submit"><span><strong><?= $e($notification['title']) ?></strong><span class="block text-sm text-slate-600"><?= $e($notification['message']) ?></span><small class="mt-2 block text-xs text-slate-500"><?= $e((new DateTimeImmutable($notification['created_at']))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y H:i')) ?></small></span><span class="text-institutional-700" aria-hidden="true">→</span></button></form>
<?php endforeach; ?></div>
<?php if ($notificationTotal > 20): ?><nav class="mt-7 flex items-center justify-between gap-4" aria-label="Páginas de notificaciones"><?php if ($notificationPage > 1): ?><a class="font-semibold text-institutional-700 underline" href="/notifications?page=<?= $notificationPage - 1 ?>">Anterior</a><?php else: ?><span></span><?php endif; ?><span class="text-sm text-slate-600">Página <?= (int) $notificationPage ?> de <?= (int) ceil($notificationTotal / 20) ?></span><?php if ($notificationPage * 20 < $notificationTotal): ?><a class="font-semibold text-institutional-700 underline" href="/notifications?page=<?= $notificationPage + 1 ?>">Siguiente</a><?php endif; ?></nav><?php endif; ?>
</main></body></html>
