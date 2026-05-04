<?php
$themeMode = 'light';
$locale = 'en';
$dir = 'ltr';
$bodyFont = 'Inter, system-ui, sans-serif';
$appName = 'Reseller Platform — Install';
?><!doctype html>
<html lang="en" dir="<?= e($dir) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($appName) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:<?= $bodyFont ?>}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="max-w-2xl mx-auto py-10 px-4 sm:px-6">
    <h1 class="text-3xl font-bold">Install Reseller Platform</h1>
    <p class="mt-1 text-slate-600">Configure database, admin user and site basics.</p>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <?php foreach (($requirements ?? []) as $name => $ok): ?>
            <div class="rounded-md p-3 border <?= $ok ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' ?>">
                <div class="font-medium"><?= e((string) $name) ?></div>
                <div class="text-xs"><?= $ok ? 'OK' : 'MISSING' ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="<?= e(url('/install')) ?>" class="mt-6 bg-white rounded-lg shadow border border-slate-200 p-6 space-y-5">
        <?= csrf_field() ?>
        <fieldset class="space-y-3">
            <legend class="font-semibold">Database</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="text-sm">Host<input name="db_host" value="127.0.0.1" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm">Port<input name="db_port" value="3306" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm">Database<input name="db_name" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm">Username<input name="db_user" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm sm:col-span-2">Password<input name="db_pass" type="password" class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
            </div>
        </fieldset>
        <fieldset class="space-y-3">
            <legend class="font-semibold">Site</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="text-sm">Site name<input name="app_name" value="Reseller Platform" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm">Site URL<input name="app_url" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2" placeholder="https://yourdomain.com"></label>
            </div>
        </fieldset>
        <fieldset class="space-y-3">
            <legend class="font-semibold">Administrator</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="text-sm">Name<input name="admin_name" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm">Email<input name="admin_email" type="email" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
                <label class="text-sm sm:col-span-2">Password<input name="admin_password" type="password" minlength="8" required class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
            </div>
        </fieldset>
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 font-semibold">Install</button>
    </form>
</div>
</body>
</html>
