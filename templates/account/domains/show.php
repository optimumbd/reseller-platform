<?php layout('layouts/app', ['title' => 'Domain']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h1 class="text-xl font-semibold font-mono"><?= e((string) ($domain->domain ?? '')) ?></h1>
            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Expires</dt><dd><?= e((string) ($domain->expires_at ?? '')) ?></dd></div>
                <div><dt class="text-slate-500">Status</dt><dd><?= e((string) ($domain->status ?? '')) ?></dd></div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="<?= e(url('/account/domains/' . (int) $domain->id . '/nameservers')) ?>" class="px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-700 text-sm">Nameservers</a>
                <form method="post" action="<?= e(url('/account/domains/' . (int) $domain->id . '/lock')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-700 text-sm">Toggle lock</button>
                </form>
                <form method="post" action="<?= e(url('/account/domains/' . (int) $domain->id . '/privacy')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-700 text-sm">Toggle privacy</button>
                </form>
                <form method="post" action="<?= e(url('/account/domains/' . (int) $domain->id . '/auto-renew')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-100 dark:bg-slate-700 text-sm">Toggle auto-renew</button>
                </form>
            </div>
        </div>
    </div>
</section>
