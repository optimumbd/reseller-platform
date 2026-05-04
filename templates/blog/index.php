<?php layout('layouts/app', ['title' => 'Blog']); ?>
<h1 class="text-2xl font-semibold">Blog</h1>
<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach (($pagination->items ?? []) as $post): ?>
        <a href="<?= e(url('/blog/' . urlencode((string) ($post->slug ?? '')))) ?>" class="block rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 hover:shadow">
            <div class="font-medium"><?= e((string) ($post->title ?? '')) ?></div>
            <div class="mt-1 text-xs text-slate-500"><?= e((string) ($post->published_at ?? '')) ?></div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 line-clamp-3"><?= e((string) ($post->excerpt ?? '')) ?></p>
        </a>
    <?php endforeach; ?>
</div>
<?= partial('pagination', ['pagination' => $pagination ?? null]) ?>
