<?php layout('layouts/app', ['title' => 'Knowledge base']); ?>
<h1 class="text-2xl font-semibold">Knowledge base</h1>
<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach (($categories ?? []) as $cat): ?>
        <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
            <div class="font-medium"><?= e((string) ($cat->name ?? '')) ?></div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300"><?= e((string) ($cat->description ?? '')) ?></p>
        </div>
    <?php endforeach; ?>
</div>
