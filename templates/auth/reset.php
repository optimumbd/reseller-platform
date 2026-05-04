<?php layout('layouts/app', ['title' => __('auth.forgot_password')]); ?>

<div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold">Reset password</h1>
    <form method="post" action="<?= e(url('/reset-password')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e((string) ($token ?? '')) ?>">
        <input name="email" type="email" required placeholder="<?= e(__('common.email')) ?>" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <input name="password" type="password" required minlength="8" placeholder="<?= e(__('common.password')) ?>" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium"><?= e(__('common.submit')) ?></button>
    </form>
</div>
