<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$displayName = \App\Presentation\DisplayName::withoutDemoMarker($user->fullName);
$firstName = explode(' ', $displayName)[0] ?: $displayName;
$csrfToken = $session->csrfToken();
$statusStyles = [
    'pending' => 'bg-amber-50 text-amber-900',
    'in_progress' => 'bg-blue-50 text-blue-800',
    'resolved' => 'bg-emerald-50 text-emerald-800',
    'closed' => 'bg-slate-100 text-slate-700',
    'cancelled' => 'bg-slate-100 text-slate-700',
];
$localDate = static fn (string $value): string => (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y · H:i');
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi panel | Soporte CSI</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3">Saltar al contenido</a>
<header class="bg-ink text-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4">
        <a href="/" class="inline-flex min-h-11 items-center gap-3 rounded-lg" aria-label="CSI GRED, inicio">
            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/30 bg-white/10 text-sm font-extrabold" aria-hidden="true">CSI</span>
            <span class="leading-tight"><strong class="block text-sm tracking-wide">GRED Lambayeque</strong><span class="block text-xs text-white/70">Soporte tecnológico · CSI</span></span>
        </a>
        <div class="flex items-center gap-3"><span class="hidden text-sm font-semibold sm:inline"><?= $e($displayName) ?></span><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button class="min-h-11 rounded-lg border border-white/30 px-4 text-sm font-semibold hover:bg-white/10">Cerrar sesión</button></form></div>
    </div>
</header>
<main id="contenido" class="mx-auto max-w-6xl px-5 py-9 sm:py-12">
    <div class="grid gap-6 md:grid-cols-[minmax(0,1.2fr)_minmax(280px,0.8fr)] md:items-center">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-institutional-700">Panel del trabajador</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Hola, <?= $e($firstName) ?></h1>
            <p class="mt-3 max-w-xl text-slate-600">Registra solicitudes de soporte tecnológico y consulta el avance de cada atención.</p>
        </div>
        <aside class="rounded-2xl border border-institutional-100 bg-gradient-to-br from-institutional-50 to-white p-5 shadow-card" aria-label="Ayuda para registrar solicitudes">
            <div class="flex items-start gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-white text-institutional-700 shadow-card" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M12 18h.01M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2.2-2.9 4" stroke-linecap="round"/><circle cx="12" cy="12" r="10"/></svg></span><div><h2 class="font-bold">¿Necesitas ayuda con tu equipo?</h2><p class="mt-1 text-sm leading-6 text-slate-600">Describe el problema y adjunta evidencias para que CSI pueda atenderte.</p></div></div>
        </aside>
    </div>

    <nav class="mt-8 grid gap-4 sm:grid-cols-3" aria-label="Accesos principales">
        <?php if ($user->can('request.create')): ?><a href="/requests/new" class="group flex min-h-28 items-center gap-4 rounded-2xl bg-institutional-700 p-5 text-white shadow-card transition-colors hover:bg-institutional-800"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-white/15 text-3xl font-light" aria-hidden="true">+</span><span><strong class="block text-lg">Nueva solicitud</strong><span class="mt-1 block text-sm text-white/85">Registra un problema o solicita soporte.</span></span><span class="ml-auto text-xl" aria-hidden="true">›</span></a><?php endif; ?>
        <?php if ($user->can('request.view_own')): ?><a href="/requests" class="group flex min-h-28 items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition-colors hover:bg-institutional-50"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-institutional-50 text-institutional-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M8 6h12M8 12h12M8 18h12" stroke-linecap="round"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg></span><span><strong class="block text-lg">Mis solicitudes</strong><span class="mt-1 block text-sm text-slate-600">Revisa el estado y detalle de tus casos.</span></span><span class="ml-auto text-xl text-institutional-700" aria-hidden="true">›</span></a><?php endif; ?>
        <a href="#guia-rapida" class="group flex min-h-28 items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition-colors hover:bg-institutional-50"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-institutional-50 text-institutional-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M12 18h.01M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2.2-2.9 4" stroke-linecap="round"/><circle cx="12" cy="12" r="10"/></svg></span><span><strong class="block text-lg">Guía rápida</strong><span class="mt-1 block text-sm text-slate-600">Consejos para registrar tu solicitud.</span></span><span class="ml-auto text-xl text-institutional-700" aria-hidden="true">›</span></a>
    </nav>

    <section class="mt-8" aria-labelledby="estado-solicitudes"><h2 id="estado-solicitudes" class="sr-only">Estado de mis solicitudes</h2><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-full bg-amber-50 text-amber-800" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/></svg></span><p class="mt-4 text-3xl font-extrabold"><?= $ownStatusCounts['pending'] ?? 0 ?></p><h3 class="font-bold">Pendientes</h3><p class="mt-1 text-sm text-slate-600">Aún no han sido atendidas.</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-full bg-blue-50 text-blue-800" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M14 7a4 4 0 0 0-5 5l-5 5a2 2 0 0 0 3 3l5-5a4 4 0 0 0 5-5l-3 3-3-3 3-3Z" stroke-linejoin="round"/></svg></span><p class="mt-4 text-3xl font-extrabold"><?= $ownStatusCounts['in_progress'] ?? 0 ?></p><h3 class="font-bold">En atención</h3><p class="mt-1 text-sm text-slate-600">CSI está trabajando en ellas.</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-800" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-4 text-3xl font-extrabold"><?= $ownStatusCounts['resolved'] ?? 0 ?></p><h3 class="font-bold">Solucionadas</h3><p class="mt-1 text-sm text-slate-600">Tienen una solución registrada.</p></article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M7 3h8l3 3v15H7z" stroke-linejoin="round"/><path d="M15 3v4h3M10 12h5M10 16h5" stroke-linecap="round"/></svg></span><p class="mt-4 text-3xl font-extrabold"><?= $ownStatusCounts['closed'] ?? 0 ?></p><h3 class="font-bold">Cerradas</h3><p class="mt-1 text-sm text-slate-600">Casos finalizados.</p></article>
    </div></section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="solicitudes-recientes"><div class="flex flex-wrap items-center justify-between gap-3"><h2 id="solicitudes-recientes" class="text-xl font-bold">Mis solicitudes recientes</h2><a href="/requests" class="inline-flex min-h-11 items-center rounded-lg px-2 text-sm font-semibold text-institutional-700 underline hover:text-institutional-800">Ver todas</a></div>
        <?php if ($recentOwnRequests === []): ?><p class="mt-4 rounded-xl bg-slate-50 p-5 text-slate-600">Aún no tienes solicitudes. Usa «Nueva solicitud» para registrar la primera.</p><?php else: ?><ol class="mt-4 space-y-2"><?php foreach ($recentOwnRequests as $recent): ?><li><a href="/requests/<?= rawurlencode($recent['public_code']) ?>" class="flex min-h-24 flex-wrap items-center gap-4 rounded-xl border border-slate-200 p-4 transition-colors hover:border-institutional-100 hover:bg-institutional-50"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-slate-100 text-institutional-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M7 3h8l3 3v15H7z" stroke-linejoin="round"/><path d="M15 3v4h3M10 12h5M10 16h5" stroke-linecap="round"/></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-institutional-700"><?= $e($recent['public_code']) ?></span><strong class="mt-1 block break-words text-sm"><?= $e($recent['title']) ?></strong><span class="mt-1 block text-xs text-slate-600"><?= $e($recent['area_name']) ?> · <?= $e($localDate($recent['created_at'])) ?></span></span><span class="rounded-full px-3 py-1.5 text-xs font-bold <?= $statusStyles[$recent['status_code']] ?? 'bg-slate-100 text-slate-700' ?>"><?= $e($recent['status_name']) ?></span><span class="text-xl text-institutional-700" aria-hidden="true">›</span></a></li><?php endforeach; ?></ol><?php endif; ?>
    </section>

    <section id="guia-rapida" class="mt-8 rounded-2xl border border-institutional-100 bg-institutional-50 p-5 sm:p-6" aria-labelledby="guia-titulo"><h2 id="guia-titulo" class="text-lg font-bold">Antes de registrar, ten en cuenta:</h2><ol class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"><li><strong class="block text-sm">Describe el problema</strong><p class="mt-1 text-sm text-slate-600">Indica qué sucede y cuándo empezó.</p></li><li><strong class="block text-sm">Adjunta evidencias</strong><p class="mt-1 text-sm text-slate-600">Una foto o captura ayuda a comprender la falla.</p></li><li><strong class="block text-sm">Indica tu área</strong><p class="mt-1 text-sm text-slate-600">Selecciona la oficina o sede correspondiente.</p></li><li><strong class="block text-sm">Sigue el avance</strong><p class="mt-1 text-sm text-slate-600">Consulta el estado y la solución en «Mis solicitudes».</p></li></ol></section>
</main>
</body>
</html>
