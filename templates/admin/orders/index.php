<?php layout('layouts/app', ['title' => __('admin.orders')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <h1 class="text-xl font-semibold"><?= e(__('admin.orders')) ?></h1>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left"><th class="py-2 pr-4">Order</th><th class="py-2 pr-4">User</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Total</th><th class="py-2 pr-4">Date</th></tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($pagination->items as $o): ?>
                <tr>
                    <td class="py-2 pr-4"><a class="text-indigo-600 hover:underline" href="<?= e(url('/admin/orders/' . (int) $o->id)) ?>">#<?= e((string) ($o->number ?? $o->id)) ?></a></td>
                    <td class="py-2 pr-4">#<?= (int) ($o->user_id ?? 0) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($o->status ?? '')) ?></td>
                    <td class="py-2 pr-4 font-mono"><?= e(money((float) ($o->total ?? 0), (string) ($o->currency ?? 'USD'))) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($o->created_at ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagination->items)): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No orders.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= partial('pagination', ['pagination' => $pagination]) ?>
    </div>
</section>
