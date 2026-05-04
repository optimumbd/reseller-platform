<?php layout('layouts/app', ['title' => __('auth.magic_link')]); ?>

<div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold"><?= e(__('auth.magic_link')) ?></h1>
    <form method="post" action="<?= e(url('/magic-link')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <input name="email" type="email" required placeholder="<?= e(__('common.email')) ?>" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium"><?= e(__('auth.send_magic_link')) ?></button>
    </form>
</div>
