<?php layout('layouts/app', ['title' => __('account.security')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h1 class="text-xl font-semibold">Change password</h1>
            <form method="post" action="<?= e(url('/account/security/password')) ?>" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?= csrf_field() ?>
                <input type="password" name="current_password" required placeholder="Current password" class="rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <input type="password" name="new_password" required minlength="8" placeholder="New password" class="rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
                <div class="sm:col-span-2"><button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Update</button></div>
            </form>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="font-medium">Two-factor authentication</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Status: <strong><?= !empty($user->totp_enabled) ? 'enabled' : 'disabled' ?></strong></p>
            <div class="mt-3 flex gap-2">
                <?php if (empty($user->totp_enabled)): ?>
                    <a href="<?= e(url('/account/security/2fa')) ?>" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">Set up 2FA</a>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/account/security/2fa/disable')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 rounded-md bg-rose-600 text-white text-sm hover:bg-rose-700">Disable 2FA</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
