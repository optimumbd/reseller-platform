<?php layout('layouts/app', ['title' => 'New ticket']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-xl font-semibold">New ticket</h1>
        <form method="post" action="<?= e(url('/account/tickets')) ?>" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <input name="subject" required placeholder="Subject" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
            <select name="priority" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2">
                <?php foreach (['low', 'normal', 'high', 'urgent'] as $p): ?>
                    <option value="<?= e($p) ?>" <?= $p === 'normal' ? 'selected' : '' ?>><?= e(ucfirst($p)) ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="message" required rows="6" placeholder="Describe your issue..." class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2"></textarea>
            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Submit</button>
        </form>
    </div>
</section>
