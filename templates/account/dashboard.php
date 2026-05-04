<?php layout('layouts/app', ['title' => __('account.overview')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?php foreach ([
                ['label' => __('account.services'), 'value' => $stats['services'] ?? 0, 'icon' => '📦'],
                ['label' => __('account.domains'), 'value' => $stats['domains'] ?? 0, 'icon' => '🌐'],
                ['label' => __('account.invoices'), 'value' => $stats['invoices_due'] ?? 0, 'icon' => '🧾'],
            ] as $card): ?>
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
                    <div class="text-2xl"><?= e($card['icon']) ?></div>
                    <div class="mt-2 text-sm text-slate-600 dark:text-slate-300"><?= e((string) $card['label']) ?></div>
                    <div class="text-2xl font-semibold"><?= (int) $card['value'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="font-medium"><?= e(__('account.services')) ?></h2>
            <?php if (empty($services)): ?>
                <p class="mt-3 text-sm text-slate-500">No services yet.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                    <?php foreach ($services as $s): ?>
                        <li class="py-2 flex items-center justify-between">
                            <span><?= e((string) ($s->label ?? $s->id)) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700"><?= e((string) ($s->status ?? '')) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="font-medium"><?= e(__('account.invoices')) ?></h2>
            <?php if (empty($invoices)): ?>
                <p class="mt-3 text-sm text-slate-500">No invoices yet.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                    <?php foreach ($invoices as $inv): ?>
                        <li class="py-2 flex items-center justify-between">
                            <a class="text-indigo-600 hover:underline" href="<?= e(url('/account/invoices/' . (int) $inv->id)) ?>">#<?= e((string) ($inv->number ?? $inv->id)) ?></a>
                            <span class="font-mono"><?= e(money((float) ($inv->total ?? 0), (string) ($inv->currency ?? 'USD'))) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
