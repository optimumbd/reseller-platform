<?php layout('layouts/app', ['title' => __('account.wallet')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h1 class="text-xl font-semibold"><?= e(__('account.wallet')) ?></h1>
            <div class="mt-4 text-3xl font-bold"><?= e(money((float) ($wallet->balance ?? 0), (string) ($wallet->currency ?? 'USD'))) ?></div>
            <p class="text-sm text-slate-500">Current balance</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
            <h2 class="font-medium">Recent transactions</h2>
            <table class="mt-3 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
                <thead><tr class="text-left"><th class="py-2 pr-4">Date</th><th class="py-2 pr-4">Type</th><th class="py-2 pr-4">Description</th><th class="py-2 pr-4 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td class="py-2 pr-4"><?= e((string) ($t->created_at ?? '')) ?></td>
                        <td class="py-2 pr-4"><?= e((string) ($t->type ?? '')) ?></td>
                        <td class="py-2 pr-4"><?= e((string) ($t->description ?? '')) ?></td>
                        <td class="py-2 pr-4 text-right font-mono"><?= e(money((float) ($t->amount ?? 0), (string) ($wallet->currency ?? 'USD'))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="4" class="py-6 text-center text-slate-500">No transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
