<?php layout('layouts/app', ['title' => __('account.profile')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold"><?= e(__('account.profile')) ?></h1>
        <form method="post" action="<?= e(url('/account/profile')) ?>" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <?php
            $fields = [
                ['name', 'common.name', 'text'],
                ['email', 'common.email', 'email'],
                ['phone', 'common.phone', 'tel'],
                ['company', 'common.company', 'text'],
                ['address', 'common.address', 'text'],
                ['city', null, 'text', 'City'],
                ['state', null, 'text', 'State'],
                ['country', null, 'text', 'Country'],
                ['postal_code', null, 'text', 'Postal code'],
            ];
            foreach ($fields as $f): ?>
                <label class="block">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-300"><?= e($f[1] ? __($f[1]) : ($f[3] ?? $f[0])) ?></span>
                    <input name="<?= e($f[0]) ?>" type="<?= e($f[2]) ?>" value="<?= e((string) ($user->{$f[0]} ?? '')) ?>" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                </label>
            <?php endforeach; ?>
            <label class="block">
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Locale</span>
                <select name="locale" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2">
                    <?php foreach (['en' => 'English', 'bn' => 'বাংলা'] as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= (string) ($user->locale ?? 'en') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300"><?= e(__('common.currency')) ?></span>
                <select name="currency" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2">
                    <?php foreach (['USD', 'BDT', 'EUR', 'GBP', 'INR'] as $c): ?>
                        <option value="<?= e($c) ?>" <?= (string) ($user->currency ?? 'USD') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.save')) ?></button>
            </div>
        </form>
    </div>
</section>
