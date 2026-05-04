<?php layout('layouts/app', ['title' => __('admin.services')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <h1 class="text-xl font-semibold"><?= e(__('admin.services')) ?></h1>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left"><th class="py-2 pr-4">ID</th><th class="py-2 pr-4">User</th><th class="py-2 pr-4">Type</th><th class="py-2 pr-4">Label</th><th class="py-2 pr-4">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($pagination->items as $s): ?>
                <tr>
                    <td class="py-2 pr-4">#<?= (int) $s->id ?></td>
                    <td class="py-2 pr-4">#<?= (int) ($s->user_id ?? 0) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($s->product_type ?? '')) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($s->label ?? '')) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($s->status ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagination->items)): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No services.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= partial('pagination', ['pagination' => $pagination]) ?>
    </div>
</section>
