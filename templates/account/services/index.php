<?php layout('layouts/app', ['title' => __('account.services')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <h1 class="text-xl font-semibold"><?= e(__('account.services')) ?></h1>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left">
                <th class="py-2 pr-4">ID</th>
                <th class="py-2 pr-4">Label</th>
                <th class="py-2 pr-4">Type</th>
                <th class="py-2 pr-4">Status</th>
                <th class="py-2 pr-4">Next due</th>
                <th class="py-2 pr-4"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($services as $s): ?>
                <tr>
                    <td class="py-2 pr-4">#<?= (int) $s->id ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($s->label ?? '')) ?></td>
                    <td class="py-2 pr-4 text-xs"><?= e((string) ($s->product_type ?? '')) ?></td>
                    <td class="py-2 pr-4"><span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700"><?= e((string) ($s->status ?? '')) ?></span></td>
                    <td class="py-2 pr-4"><?= e((string) ($s->next_due_at ?? '')) ?></td>
                    <td class="py-2 pr-4"><a class="text-indigo-600 hover:underline" href="<?= e(url('/account/services/' . (int) $s->id)) ?>"><?= e(__('common.view')) ?></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($services)): ?>
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">No services yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
