<?php layout('layouts/app', ['title' => 'Domain search']); ?>
<?php $q = (string) ($query ?? ''); ?>
<section class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold">Find your domain</h1>
    <form method="get" action="<?= e(url('/domains/search')) ?>" class="mt-4 flex flex-col sm:flex-row gap-2">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="example.com" class="flex-1 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2" />
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.search')) ?></button>
    </form>
</section>

<?php if (!empty($results)): ?>
<section class="mt-6 grid gap-3">
    <?php foreach ($results as $r): ?>
        <?php $available = (bool) ($r['available'] ?? false); ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-800 rounded-md border border-slate-200 dark:border-slate-700 px-4 py-3">
            <div>
                <div class="font-mono text-lg"><?= e((string) $r['domain']) ?></div>
                <div class="text-xs <?= $available ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $available ? e(__('common.available')) : e(__('common.taken')) ?></div>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($available): ?>
                    <div class="text-sm text-slate-600 dark:text-slate-300"><?= e(money((float) ($r['price'] ?? 0), (string) ($r['currency'] ?? 'USD'))) ?> / yr</div>
                    <form method="post" action="<?= e(url('/cart/add')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="domain_register">
                        <input type="hidden" name="domain" value="<?= e((string) $r['domain']) ?>">
                        <input type="hidden" name="years" value="1">
                        <button type="submit" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700"><?= e(__('common.add_to_cart')) ?></button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/domains/whois?domain=' . urlencode((string) $r['domain']))) ?>" class="text-sm text-indigo-600 hover:underline">WHOIS</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php elseif ($q !== ''): ?>
<p class="mt-4 text-sm text-slate-500">No results.</p>
<?php endif; ?>
