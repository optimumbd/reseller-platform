<?php layout('layouts/app', ['title' => 'Domain transfer']); ?>
<section class="max-w-2xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold">Transfer your domain</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Enter your domain and EPP code to start a transfer.</p>
    <form method="post" action="<?= e(url('/domains/transfer')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>
        <input name="domain" type="text" required placeholder="example.com" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <input name="epp" type="text" required placeholder="EPP / Auth code" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="w-full rounded-md bg-indigo-600 text-white py-2 hover:bg-indigo-700">Start transfer</button>
    </form>
</section>
