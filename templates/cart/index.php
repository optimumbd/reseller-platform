<?php layout('layouts/app', ['title' => __('common.cart')]); ?>
<?php
$items = (array) ($cart['items'] ?? []);
$subtotal = (float) array_sum(array_map(fn ($i) => (float) ($i['price'] ?? 0) * (int) ($i['years'] ?? 1), $items));
?>
<section class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
    <h1 class="text-2xl font-semibold"><?= e(__('common.cart')) ?></h1>
    <?php if (empty($items)): ?>
        <p class="mt-4 text-slate-500"><?= e(__('cart.empty')) ?></p>
        <a href="<?= e(url('/domains/search')) ?>" class="mt-4 inline-block px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.continue_shopping')) ?></a>
    <?php else: ?>
        <div class="mt-6 divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($items as $i => $item): ?>
            <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="font-medium"><?= e((string) ($item['title'] ?? $item['domain'] ?? 'Item')) ?></div>
                    <div class="text-xs text-slate-500"><?= e((string) ($item['type'] ?? 'item')) ?> · <?= (int) ($item['years'] ?? 1) ?> <?= e(__('common.years')) ?></div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-sm font-mono"><?= e(money((float) ($item['price'] ?? 0) * (int) ($item['years'] ?? 1), (string) ($item['currency'] ?? 'USD'))) ?></div>
                    <form method="post" action="<?= e(url('/cart/remove/' . (int) $i)) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-xs text-rose-600 hover:underline"><?= e(__('common.remove')) ?></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-6 flex items-center justify-between">
            <a href="<?= e(url('/domains/search')) ?>" class="text-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600"><?= e(__('common.continue_shopping')) ?></a>
            <div class="text-right">
                <div class="text-xs text-slate-500"><?= e(__('common.subtotal')) ?></div>
                <div class="text-xl font-semibold"><?= e(money($subtotal, current_currency())) ?></div>
                <a href="<?= e(url('/checkout')) ?>" class="mt-3 inline-block px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('cart.go_to_checkout')) ?></a>
            </div>
        </div>
    <?php endif; ?>
</section>
