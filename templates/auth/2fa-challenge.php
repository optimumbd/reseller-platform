<?php layout('layouts/app', ['title' => 'Two-factor authentication']); ?>

<div class="max-w-md mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold">Verify your identity</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Enter the 6-digit code from your authenticator app.</p>
    <form method="post" action="<?= e(url('/two-factor/verify')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <input name="code" inputmode="numeric" pattern="[0-9]*" minlength="6" maxlength="8" required autofocus placeholder="123 456" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-3 text-center text-xl tracking-widest" />
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium">Verify</button>
    </form>
</div>
