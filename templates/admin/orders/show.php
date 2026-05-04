<?php layout('layouts/app', ['title' => 'Order']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">Order #<?= e((string) ($order->number ?? $order->id)) ?></h1>
        <pre class="mt-4 p-3 rounded bg-slate-50 dark:bg-slate-900 text-xs overflow-auto"><?= e(json_encode($order->toArray(), JSON_PRETTY_PRINT)) ?></pre>
    </div>
</section>
