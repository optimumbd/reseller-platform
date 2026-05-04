<?php layout('layouts/app', ['title' => 'Order']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">Order #<?= e((string) ($order->number ?? $order->id)) ?></h1>
        <p class="text-sm text-slate-500 mt-1">Placed <?= e((string) ($order->created_at ?? '')) ?></p>
        <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach (($items ?? []) as $it): ?>
                <li class="py-2 flex items-center justify-between">
                    <span><?= e((string) ($it->description ?? '')) ?></span>
                    <span class="font-mono"><?= e(money((float) ($it->total ?? $it->price ?? 0), (string) ($order->currency ?? 'USD'))) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-3 text-right font-semibold">Total: <?= e(money((float) ($order->total ?? 0), (string) ($order->currency ?? 'USD'))) ?></div>
    </div>
</section>
