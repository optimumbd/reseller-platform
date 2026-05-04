<?php layout('layouts/app', ['title' => (string) ($article->title ?? 'Article')]); ?>
<article class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold"><?= e((string) ($article->title ?? '')) ?></h1>
    <div class="mt-4 text-slate-700 dark:text-slate-200"><?= (string) ($article->body ?? '') ?></div>
</article>
