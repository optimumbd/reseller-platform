<?php layout('layouts/app', ['title' => 'Domain generator']); ?>
<section class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold">Domain name generator</h1>
    <form method="get" action="<?= e(url('/domains/generator')) ?>" class="mt-4 flex gap-2">
        <input type="text" name="keyword" value="<?= e((string) ($keyword ?? '')) ?>" placeholder="brand keyword" class="flex-1 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Generate</button>
    </form>
    <?php if (!empty($suggestions)): ?>
    <ul class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
        <?php foreach ($suggestions as $s): ?>
            <li class="px-3 py-2 rounded-md bg-slate-50 dark:bg-slate-900 font-mono"><?= e((string) $s) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
