<?php layout('layouts/app', ['title' => 'Invoice']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">Invoice #<?= e((string) ($invoice->number ?? $invoice->id)) ?></h1>
        <p class="text-sm text-slate-500 mt-1">Status: <?= e((string) ($invoice->status ?? '')) ?> · Due <?= e((string) ($invoice->due_date ?? '')) ?></p>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr><th class="py-2 text-left">Description</th><th class="py-2 text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach (($items ?? []) as $it): ?>
                <tr>
                    <td class="py-2"><?= e((string) ($it->description ?? '')) ?></td>
                    <td class="py-2 text-right font-mono"><?= e(money((float) ($it->total ?? $it->amount ?? 0), (string) ($invoice->currency ?? 'USD'))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td class="py-2 text-right font-semibold">Total</td><td class="py-2 text-right font-mono font-semibold"><?= e(money((float) ($invoice->total ?? 0), (string) ($invoice->currency ?? 'USD'))) ?></td></tr>
            </tfoot>
        </table>
        <?php if ((string) ($invoice->status ?? '') !== 'paid'): ?>
        <form method="post" action="<?= e(url('/account/invoices/' . (int) $invoice->id . '/pay')) ?>" class="mt-6 flex flex-col sm:flex-row gap-2 items-stretch">
            <?= csrf_field() ?>
            <select name="gateway" class="flex-1 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2">
                <?php foreach (($gateways ?? []) as $key => $g): ?>
                    <option value="<?= e((string) $key) ?>"><?= e(ucfirst((string) $key)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('checkout.pay_now')) ?></button>
        </form>
        <?php endif; ?>
    </div>
</section>
