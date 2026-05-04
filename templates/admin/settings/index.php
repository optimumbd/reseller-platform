<?php layout('layouts/app', ['title' => __('admin.settings')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold"><?= e(__('admin.settings')) ?></h1>
        <form method="post" action="<?= e(url('/admin/settings')) ?>" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <?= csrf_field() ?>
            <?php
            $known = [
                'site_name' => 'Site name',
                'support_email' => 'Support email',
                'default_currency' => 'Default currency',
                'default_locale' => 'Default locale',
                'company_address' => 'Company address',
                'tax_rate' => 'Tax rate (%)',
            ];
            foreach ($known as $key => $label): ?>
                <label class="block">
                    <span class="text-xs font-medium"><?= e($label) ?></span>
                    <input name="settings[<?= e($key) ?>]" value="<?= e((string) ($settings[$key] ?? '')) ?>" class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                </label>
            <?php endforeach; ?>
            <div class="sm:col-span-2"><button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.save')) ?></button></div>
        </form>
    </div>
</section>
