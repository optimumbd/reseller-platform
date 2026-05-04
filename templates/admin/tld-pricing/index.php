<?php layout('layouts/app', ['title' => __('admin.pricing')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
            <h1 class="text-xl font-semibold"><?= e(__('admin.pricing')) ?></h1>
            <form method="post" action="<?= e(url('/admin/pricing')) ?>" class="mt-4">
                <?= csrf_field() ?>
                <table class="min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
                    <thead><tr class="text-left">
                        <th class="py-2 pr-4">TLD</th><th class="py-2 pr-4">Register</th><th class="py-2 pr-4">Renew</th><th class="py-2 pr-4">Transfer</th><th class="py-2 pr-4">Currency</th><th class="py-2 pr-4">Active</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach (($rows ?? []) as $r): ?>
                        <tr>
                            <td class="py-1 pr-4 font-mono">.<?= e(ltrim((string) ($r->tld ?? ''), '.')) ?></td>
                            <td class="py-1 pr-4"><input name="rows[<?= (int) $r->id ?>][register_price]" type="number" step="0.01" value="<?= e((string) ($r->register_price ?? 0)) ?>" class="w-24 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1" /></td>
                            <td class="py-1 pr-4"><input name="rows[<?= (int) $r->id ?>][renew_price]" type="number" step="0.01" value="<?= e((string) ($r->renew_price ?? 0)) ?>" class="w-24 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1" /></td>
                            <td class="py-1 pr-4"><input name="rows[<?= (int) $r->id ?>][transfer_price]" type="number" step="0.01" value="<?= e((string) ($r->transfer_price ?? 0)) ?>" class="w-24 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1" /></td>
                            <td class="py-1 pr-4"><input name="rows[<?= (int) $r->id ?>][currency]" value="<?= e((string) ($r->currency ?? 'USD')) ?>" class="w-20 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1 uppercase" /></td>
                            <td class="py-1 pr-4"><input name="rows[<?= (int) $r->id ?>][is_active]" type="checkbox" value="1" <?= !empty($r->is_active) ? 'checked' : '' ?> /></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="mt-4 px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.save')) ?></button>
            </form>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="font-medium">Add TLD</h2>
            <form method="post" action="<?= e(url('/admin/pricing/create')) ?>" class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-2 text-sm">
                <?= csrf_field() ?>
                <input name="tld" required placeholder=".com" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <input name="register_price" type="number" step="0.01" required placeholder="Register" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <input name="renew_price" type="number" step="0.01" required placeholder="Renew" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <input name="transfer_price" type="number" step="0.01" required placeholder="Transfer" class="rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <button type="submit" class="rounded bg-indigo-600 text-white hover:bg-indigo-700 px-3 py-2">Add</button>
            </form>
        </div>
    </div>
</section>
