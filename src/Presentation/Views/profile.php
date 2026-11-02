<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$displayName = \App\Presentation\DisplayName::withoutDemoMarker($user->fullName);
?>
<!doctype html>
<html lang="es-PE">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi perfil | CSI · GRED</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="min-h-screen bg-paper text-ink">
<header class="bg-institutional-800 text-white"><div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-5 py-4"><a href="/dashboard" class="font-extrabold">CSI - GRED</a><a href="/dashboard" class="inline-flex min-h-11 items-center rounded-lg border border-white/40 px-4 text-sm font-bold hover:bg-white/10">Volver al panel</a></div></header>
<main class="mx-auto max-w-4xl px-5 py-10"><p class="text-sm font-bold text-institutional-700">Mi cuenta</p><h1 class="mt-2 text-3xl font-extrabold">Perfil</h1><p class="mt-2 text-slate-600">Datos de tu cuenta en el sistema de soporte de la GRED Lambayeque.</p>
  <section class="mt-7 rounded-2xl border border-slate-200 bg-white p-6 shadow-card" aria-label="Datos de la cuenta"><dl class="divide-y divide-slate-200"><div class="py-4 first:pt-0"><dt class="text-sm font-bold text-slate-600">Nombres y apellidos</dt><dd class="mt-1 break-words text-lg font-semibold"><?= $e($displayName) ?></dd></div><div class="py-4"><dt class="text-sm font-bold text-slate-600">Correo electrónico</dt><dd class="mt-1 break-all text-lg font-semibold"><?= $e($user->email) ?></dd></div><div class="py-4 last:pb-0"><dt class="text-sm font-bold text-slate-600">Rol</dt><dd class="mt-1 text-lg font-semibold"><?= $e($user->roleName) ?></dd></div></dl></section>
</main>
</body></html>
