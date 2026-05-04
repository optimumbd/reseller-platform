<?php layout('layouts/app', ['title' => 'DNS lookup']); ?>
<section class="max-w-3xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold">DNS lookup</h1>
    <form method="get" action="<?= e(url('/domains/dns-lookup')) ?>" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
        <input type="text" name="domain" value="<?= e((string) ($domain ?? '')) ?>" placeholder="example.com" class="sm:col-span-2 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <select name="type" class="rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2">
            <?php foreach (['A', 'AAAA', 'MX', 'NS', 'TXT', 'CNAME', 'SOA'] as $t): ?>
                <option value="<?= e($t) ?>" <?= ($type ?? 'A') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="sm:col-span-3 px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Lookup</button>
    </form>
    <?php if (!empty($records)): ?>
        <pre class="mt-6 p-4 rounded-md bg-slate-50 dark:bg-slate-900 text-xs"><?= e(json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    <?php endif; ?>
</section>
