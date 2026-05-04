<?php layout('layouts/app', ['title' => __('auth.register')]); ?>

<div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white"><?= e(__('auth.register')) ?></h1>
    <form method="post" action="<?= e(url('/register')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium" for="name"><?= e(__('common.name')) ?></label>
            <input id="name" name="name" type="text" required class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        </div>
        <div>
            <label class="block text-sm font-medium" for="email"><?= e(__('common.email')) ?></label>
            <input id="email" name="email" type="email" required class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        </div>
        <div>
            <label class="block text-sm font-medium" for="password"><?= e(__('common.password')) ?></label>
            <input id="password" name="password" type="password" minlength="8" required class="mt-1 block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        </div>
        <label class="inline-flex items-start gap-2 text-sm">
            <input type="checkbox" name="agree" value="1" required class="rounded mt-0.5" />
            <span><?= e(__('auth.agree_terms')) ?></span>
        </label>
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium"><?= e(__('auth.register')) ?></button>
    </form>
    <div class="mt-6 text-sm text-center">
        <?= e(__('auth.have_account')) ?> <a href="<?= e(url('/login')) ?>" class="text-indigo-600 hover:underline"><?= e(__('auth.login')) ?></a>
    </div>
</div>
