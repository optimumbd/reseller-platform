<?php layout('layouts/app', ['title' => 'Service']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">Service #<?= (int) $service->id ?> — <?= e((string) ($service->label ?? '')) ?></h1>
        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Type</dt><dd><?= e((string) ($service->product_type ?? '')) ?></dd></div>
            <div><dt class="text-slate-500">Status</dt><dd><?= e((string) ($service->status ?? '')) ?></dd></div>
            <div><dt class="text-slate-500">Created</dt><dd><?= e((string) ($service->created_at ?? '')) ?></dd></div>
            <div><dt class="text-slate-500">Next due</dt><dd><?= e((string) ($service->next_due_at ?? '')) ?></dd></div>
        </dl>
    </div>
</section>
