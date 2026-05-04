<?php layout('layouts/app', ['title' => 'Nameservers']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">Nameservers — <?= e((string) ($domain->domain ?? '')) ?></h1>
        <form method="post" action="<?= e(url('/account/domains/' . (int) $domain->id . '/nameservers')) ?>" class="mt-4 space-y-2">
            <?= csrf_field() ?>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <input name="nameservers[]" value="<?= e((string) ($hosts[$i] ?? '')) ?>" placeholder="ns<?= $i + 1 ?>.example.com" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 font-mono" />
            <?php endfor; ?>
            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.save')) ?></button>
        </form>
    </div>
</section>
