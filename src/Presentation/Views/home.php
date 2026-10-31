<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10283F">
    <meta name="description" content="Plataforma de soporte tecnológico del Centro de Sistemas de Información de la GRED Lambayeque.">
    <title>Soporte CSI | GRED Lambayeque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/gsap.min.js" defer></script>
    <script src="/assets/ScrollTrigger.min.js" defer></script>
    <script src="/assets/home-motion.js" defer></script>
    <style>
        :root { --navy:#10283f; --teal:#176b87; --lime:#d6e5a5; --mist:#eff5f7; }
        body { font-family:'DM Sans',sans-serif; }
        h1,h2,h3,.display { font-family:'Manrope','DM Sans',sans-serif; }
        .grid-paper { background-image:linear-gradient(rgba(16,40,63,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(16,40,63,.045) 1px,transparent 1px);background-size:36px 36px; }
        .focus-ring:focus-visible { outline:3px solid #d6e5a5;outline-offset:3px; }
    </style>
</head>
<body class="min-h-screen">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-ink">Saltar al contenido</a>
<header class="border-b border-white/10 bg-ink text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
        <a href="/" class="focus-ring flex items-center gap-3 rounded-lg" aria-label="Soporte CSI, inicio">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-institutional-800" aria-hidden="true">
                <svg viewBox="0 0 32 32" class="h-7 w-7" fill="none"><path d="M5 25V7h4l7 10 7-10h4v18h-5V15l-6 8-6-8v10H5Z" fill="currentColor"/><circle cx="26" cy="25" r="3" fill="#91AD49"/></svg>
            </span>
            <span><span class="block text-sm font-bold tracking-wide">CSI · GRED</span><span class="block text-xs text-white/65">Soporte tecnológico</span></span>
        </a>
        <?php if ($user !== null): ?>
            <div class="flex items-center gap-3"><span class="hidden text-sm text-white/80 sm:inline"><?= $e($user->fullName) ?></span><a href="/dashboard" class="focus-ring rounded-lg border border-white/30 px-3 py-2 text-sm font-semibold">Mi panel</a></div>
        <?php else: ?>
            <span class="hidden items-center gap-2 rounded-full border border-white/20 px-3 py-1.5 text-xs font-medium text-white/80 sm:flex"><span class="h-2 w-2 rounded-full bg-lime-300"></span> Centro de Sistemas de Información</span>
        <?php endif; ?>
    </div>
</header>
<main id="contenido">
    <section class="relative isolate overflow-hidden bg-ink text-white">
        <div class="grid-paper absolute inset-0 -z-10 opacity-40"></div>
        <div class="absolute -right-32 -top-40 -z-10 h-[34rem] w-[34rem] rounded-full border border-white/10"></div>
        <div class="absolute -right-12 -top-20 -z-10 h-[26rem] w-[26rem] rounded-full border border-white/10"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.1fr_.9fr] lg:py-28">
            <div class="max-w-2xl">
                <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3.5 py-2 text-xs font-semibold tracking-wide text-white/85"><span class="h-1.5 w-1.5 rounded-full bg-lime-300"></span> ATENCIÓN DE SOPORTE · GRED LAMBAYEQUE</p>
                <h1 data-motion="hero" class="display max-w-xl text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-[3.65rem]">Tu trabajo sigue.<br><span class="text-[#c8dc91]">Nosotros resolvemos</span> lo técnico.</h1>
                <p data-motion="hero" class="mt-6 max-w-lg text-base leading-7 text-slate-300 sm:text-lg">Registra una solicitud, comparte lo que ocurre y sigue cada avance con el equipo de soporte de la GRED Lambayeque.</p>
                <div data-motion="hero" class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#acceso" class="focus-ring inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-[#d6e5a5] px-5 py-3 text-sm font-bold text-ink shadow-lg shadow-black/10 transition hover:bg-[#e3edc1]">
                        Ingresar al sistema <span aria-hidden="true">→</span>
                    </a>
                    <a href="#como-funciona" class="focus-ring inline-flex min-h-12 items-center justify-center rounded-lg border border-white/25 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Conoce cómo funciona</a>
                </div>
                <div class="mt-10 flex items-center gap-3 text-sm text-slate-300"><span class="grid h-8 w-8 place-items-center rounded-full bg-white/10 text-[#c8dc91]" aria-hidden="true">✓</span> Atención centralizada, con seguimiento en cada etapa.</div>
            </div>
            <div id="acceso" class="relative mx-auto w-full max-w-md scroll-mt-8">
                <div class="absolute -inset-3 -rotate-2 rounded-3xl border border-white/10 bg-white/[.03]"></div>
                <div class="relative rounded-2xl border border-white/10 bg-white p-6 text-ink shadow-2xl shadow-black/25 sm:p-8">
                    <div class="mb-7 flex items-start justify-between gap-3">
                        <div><p class="text-xs font-bold uppercase tracking-[.15em] text-institutional-700">Portal de atención</p><h2 class="mt-2 text-2xl font-extrabold">Bienvenido</h2><p class="mt-1 text-sm text-slate-600">Ingresa con tu cuenta institucional.</p></div>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-institutional-50 text-institutional-700" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4.5 6v5.3c0 4.7 3.2 8 7.5 9.7 4.3-1.7 7.5-5 7.5-9.7V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    </div>
                    <?php if ($user !== null): ?>
                        <div class="rounded-lg bg-institutional-50 p-4"><p class="font-semibold">Sesión iniciada</p><p class="mt-1 text-sm text-slate-600">Accede a tu panel para continuar.</p><a href="/dashboard" class="mt-3 inline-flex min-h-11 items-center rounded-lg bg-institutional-700 px-4 text-sm font-bold text-white">Abrir mi panel</a></div>
                    <?php else: ?>
                    <form action="/login" method="post" class="space-y-5" aria-describedby="login-note">
                        <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                        <div><label for="email" class="mb-2 block text-sm font-semibold">Correo institucional</label><input id="email" name="email" type="email" autocomplete="username" placeholder="nombre@regionlambayeque.gob.pe" class="focus-ring min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-ink placeholder:text-slate-400" required></div>
                        <div><div class="mb-2 flex items-center justify-between gap-3"><label for="password" class="text-sm font-semibold">Contraseña</label><span class="text-xs text-slate-500">Acceso institucional</span></div><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" class="focus-ring min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-ink placeholder:text-slate-400" required></div>
                        <p id="login-note" class="rounded-lg <?= $error !== null ? 'bg-rose-50 text-rose-900' : 'bg-institutional-50 text-institutional-800' ?> px-3.5 py-3 text-xs leading-5" role="status"><?= $e($error ?? 'Usa las credenciales de tu cuenta institucional.') ?></p>
                        <button type="submit" class="min-h-12 w-full rounded-lg bg-institutional-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-institutional-800">Ingresar de forma segura</button>
                    </form>
                    <?php endif; ?>
                    <div class="mt-6 flex items-center justify-center gap-2 border-t border-slate-100 pt-5 text-xs text-slate-500"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="8" width="12" height="9" rx="2"/><path d="M7 8V6a3 3 0 0 1 6 0v2"/></svg> Conexión protegida para el personal autorizado</div>
                </div>
                <div class="absolute -bottom-5 -left-5 hidden items-center gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3 shadow-card sm:flex"><span class="grid h-9 w-9 place-items-center rounded-lg bg-accent-50 text-accent-700" aria-hidden="true">↗</span><span><span class="block text-xs font-bold">Seguimiento claro</span><span class="block text-[11px] text-slate-500">Cada solicitud, un historial</span></span></div>
            </div>
        </div>
    </section>
    <section id="como-funciona" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
        <div class="mx-auto max-w-2xl text-center"><p class="text-xs font-bold uppercase tracking-[.16em] text-institutional-700">Soporte que acompaña</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">De la solicitud a la solución</h2><p class="mt-4 text-base leading-7 text-slate-600">Un canal para ordenar la atención y mantenerte al tanto de lo que sucede.</p></div>
        <div class="mt-10 grid gap-4 sm:grid-cols-3 sm:gap-5">
            <article data-motion="step" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-xl bg-institutional-50 font-bold text-institutional-700">01</span><h3 class="mt-5 text-lg font-bold">Cuéntanos qué necesitas</h3><p class="mt-2 text-sm leading-6 text-slate-600">Describe el inconveniente y adjunta una evidencia para ayudarnos a entenderlo mejor.</p></article>
            <article data-motion="step" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-xl bg-accent-50 font-bold text-accent-700">02</span><h3 class="mt-5 text-lg font-bold">El equipo lo atiende</h3><p class="mt-2 text-sm leading-6 text-slate-600">El personal técnico recibe el caso y registra los avances de la atención.</p></article>
            <article data-motion="step" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="grid h-11 w-11 place-items-center rounded-xl bg-institutional-50 font-bold text-institutional-700">03</span><h3 class="mt-5 text-lg font-bold">Sigue el resultado</h3><p class="mt-2 text-sm leading-6 text-slate-600">Consulta el estado, el historial y la solución desde un solo lugar.</p></article>
        </div>
    </section>
    <section class="border-y border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-8"><div><p class="text-sm font-bold">¿Necesitas ayuda para acceder?</p><p class="mt-1 text-sm text-slate-600">Comunícate con el Centro de Sistemas de Información de tu sede.</p></div><span class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Atención al personal GRED</span></div></section>
</main>
<footer class="bg-ink text-white/70"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-8"><span class="font-semibold text-white">GRED Lambayeque · Centro de Sistemas de Información</span><span>Plataforma interna de gestión de soporte tecnológico</span></div></footer>
</body>
</html>
