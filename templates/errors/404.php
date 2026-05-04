<?php layout('layouts/app', ['title' => 'Not found']); ?>
<div class="text-center py-20">
    <div class="text-6xl">404</div>
    <h1 class="mt-2 text-2xl font-semibold">Page not found</h1>
    <p class="mt-2 text-slate-600 dark:text-slate-300">The page you're looking for doesn't exist.</p>
    <a href="<?= e(url('/')) ?>" class="mt-6 inline-block px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Back home</a>
</div>
