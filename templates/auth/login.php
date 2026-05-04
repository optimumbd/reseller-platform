<?php layout('layouts/app', ['title' => __('auth.login')]); ?>

<div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white"><?= e(__('auth.login')) ?></h1>
    <form method="post" action="<?= e(url('/login')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" for="email"><?= e(__('common.email')) ?></label>
            <input id="email" name="email" type="email" autocomplete="email" required class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" for="password"><?= e(__('common.password')) ?></label>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="rounded" /> <?= e(__('auth.remember_me')) ?></label>
            <a href="<?= e(url('/forgot-password')) ?>" class="text-indigo-600 hover:underline"><?= e(__('auth.forgot_password')) ?></a>
        </div>
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium"><?= e(__('auth.login')) ?></button>
    </form>
    <div class="mt-6 text-sm text-center text-slate-600 dark:text-slate-300">
        <?= e(__('auth.no_account')) ?> <a href="<?= e(url('/register')) ?>" class="text-indigo-600 hover:underline"><?= e(__('auth.register')) ?></a>
    </div>
    <div class="mt-3 text-center text-sm">
        <a href="<?= e(url('/magic-link')) ?>" class="text-slate-600 dark:text-slate-300 hover:text-indigo-600"><?= e(__('auth.magic_link')) ?></a>
    </div>
</div>
