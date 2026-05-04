<?php
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH) ?: '/';
$items = [
    '/admin' => [__('admin.dashboard'), '📊'],
    '/admin/customers' => [__('admin.customers'), '👥'],
    '/admin/domains' => [__('admin.domains'), '🌐'],
    '/admin/services' => [__('admin.services'), '📦'],
    '/admin/orders' => [__('admin.orders'), '🧾'],
    '/admin/invoices' => [__('admin.invoices'), '💳'],
    '/admin/pricing' => [__('admin.pricing'), '🏷️'],
    '/admin/settings' => [__('admin.settings'), '⚙️'],
];
?>
<aside class="lg:col-span-1">
    <nav class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-2">
        <?php foreach ($items as $href => [$label, $icon]): ?>
            <?php $active = $path === $href || ($href !== '/admin' && str_starts_with($path, $href)); ?>
            <a href="<?= e(url($href)) ?>" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm <?= $active ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200 font-medium' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50' ?>">
                <span><?= e($icon) ?></span>
                <span><?= e((string) $label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
