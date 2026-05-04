<?php layout('layouts/app', ['title' => __('account.invoices')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <h1 class="text-xl font-semibold"><?= e(__('account.invoices')) ?></h1>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left">
                <th class="py-2 pr-4">Invoice</th>
                <th class="py-2 pr-4">Status</th>
                <th class="py-2 pr-4">Total</th>
                <th class="py-2 pr-4">Due</th>
                <th class="py-2 pr-4"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td class="py-2 pr-4 font-mono">#<?= e((string) ($inv->number ?? $inv->id)) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($inv->status ?? '')) ?></td>
                    <td class="py-2 pr-4 font-mono"><?= e(money((float) ($inv->total ?? 0), (string) ($inv->currency ?? 'USD'))) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($inv->due_date ?? '')) ?></td>
                    <td class="py-2 pr-4"><a class="text-indigo-600 hover:underline" href="<?= e(url('/account/invoices/' . (int) $inv->id)) ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No invoices yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
