<?php layout('layouts/app', ['title' => __('account.tickets')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold"><?= e(__('account.tickets')) ?></h1>
            <a href="<?= e(url('/account/tickets/new')) ?>" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">New ticket</a>
        </div>
        <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <?php foreach ($tickets as $t): ?>
                <li class="py-2 flex items-center justify-between">
                    <a class="text-indigo-600 hover:underline" href="<?= e(url('/account/tickets/' . (int) $t->id)) ?>"><?= e((string) ($t->number ?? $t->id)) ?> — <?= e((string) ($t->subject ?? '')) ?></a>
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700"><?= e((string) ($t->status ?? '')) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?>
                <li class="py-6 text-center text-slate-500">No tickets yet.</li>
            <?php endif; ?>
        </ul>
    </div>
</section>
