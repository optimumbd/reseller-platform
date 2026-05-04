<?php layout('layouts/app', ['title' => 'Server error']); ?>
<div class="text-center py-20">
    <div class="text-6xl">500</div>
    <h1 class="mt-2 text-2xl font-semibold">Something went wrong</h1>
    <p class="mt-2 text-slate-600 dark:text-slate-300">Please try again in a moment.</p>
    <?php if (config('app.debug') && !empty($message)): ?>
        <pre class="mt-4 mx-auto max-w-2xl text-left text-xs p-4 rounded bg-slate-100 dark:bg-slate-900 overflow-auto"><?= e((string) $message) ?></pre>
    <?php endif; ?>
</div>
