<?php layout('layouts/app', ['title' => 'WHOIS lookup']); ?>
<section class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold">WHOIS lookup</h1>
    <form method="get" action="<?= e(url('/domains/whois')) ?>" class="mt-4 flex gap-2">
        <input type="text" name="domain" value="<?= e((string) ($domain ?? '')) ?>" placeholder="example.com" class="flex-1 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Lookup</button>
    </form>
    <?php if (!empty($whois)): ?>
        <pre class="mt-6 max-h-96 overflow-auto p-4 rounded-md bg-slate-50 dark:bg-slate-900 text-xs"><?= e((string) $whois) ?></pre>
    <?php endif; ?>
</section>
