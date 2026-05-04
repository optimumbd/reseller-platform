<?php layout('layouts/app', ['title' => 'Customer']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold"><?= e((string) ($user->name ?? '')) ?></h1>
        <p class="text-sm text-slate-500"><?= e((string) ($user->email ?? '')) ?></p>
        <form method="post" action="<?= e(url('/admin/customers/' . (int) $user->id)) ?>" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <?= csrf_field() ?>
            <label>Name<input name="name" value="<?= e((string) ($user->name ?? '')) ?>" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 mt-1" /></label>
            <label>Email<input name="email" value="<?= e((string) ($user->email ?? '')) ?>" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 mt-1" /></label>
            <label>Role
                <select name="role" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 mt-1">
                    <?php foreach (['customer', 'reseller', 'moderator', 'admin'] as $r): ?>
                        <option value="<?= e($r) ?>" <?= (string) ($user->role ?? 'customer') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status
                <select name="status" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 mt-1">
                    <?php foreach (['active', 'suspended', 'banned'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= (string) ($user->status ?? 'active') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="sm:col-span-2"><button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.save')) ?></button></div>
        </form>
    </div>
</section>
