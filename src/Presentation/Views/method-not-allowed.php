<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html><html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Método no permitido | Soporte CSI</title><link rel="stylesheet" href="/assets/app.css"></head><body class="grid min-h-screen place-items-center bg-paper p-5 text-ink"><main class="max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-card"><p class="text-sm font-bold text-amber-800">Error 405</p><h1 class="mt-2 text-2xl font-extrabold">Método no permitido</h1><p class="mt-3 text-slate-600"><?= $e($error ?? 'La operación no admite este método HTTP.') ?></p><a class="mt-6 inline-flex min-h-11 items-center rounded-lg bg-institutional-700 px-4 font-bold text-white" href="/">Volver al portal</a></main></body></html>
