<?php layout('layouts/app', ['title' => (string) ($post->title ?? 'Post')]); ?>
<article class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold"><?= e((string) ($post->title ?? '')) ?></h1>
    <div class="mt-1 text-xs text-slate-500"><?= e((string) ($post->published_at ?? '')) ?></div>
    <div class="mt-4 text-slate-700 dark:text-slate-200"><?= (string) ($post->body ?? '') ?></div>
</article>
