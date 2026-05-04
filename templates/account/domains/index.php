<?php layout('layouts/app', ['title' => __('account.domains')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <h1 class="text-xl font-semibold"><?= e(__('account.domains')) ?></h1>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left">
                <th class="py-2 pr-4">Domain</th>
                <th class="py-2 pr-4">Expires</th>
                <th class="py-2 pr-4">Auto-renew</th>
                <th class="py-2 pr-4">Lock</th>
                <th class="py-2 pr-4">Privacy</th>
                <th class="py-2 pr-4"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($domains as $d): ?>
                <tr>
                    <td class="py-2 pr-4 font-mono"><?= e((string) ($d->domain ?? '')) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($d->expires_at ?? '')) ?></td>
                    <td class="py-2 pr-4"><?= !empty($d->auto_renew) ? 'on' : 'off' ?></td>
                    <td class="py-2 pr-4"><?= !empty($d->is_locked) ? 'locked' : 'unlocked' ?></td>
                    <td class="py-2 pr-4"><?= !empty($d->privacy_enabled) ? 'on' : 'off' ?></td>
                    <td class="py-2 pr-4"><a class="text-indigo-600 hover:underline" href="<?= e(url('/account/domains/' . (int) $d->id)) ?>">Manage</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($domains)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">No domains yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
