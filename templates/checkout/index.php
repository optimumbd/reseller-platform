<?php layout('layouts/app', ['title' => __('common.checkout')]); ?>
<?php
$items = (array) ($cart['items'] ?? []);
$subtotal = (float) array_sum(array_map(fn ($i) => (float) ($i['price'] ?? 0) * (int) ($i['years'] ?? 1), $items));
?>
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
        <h1 class="text-2xl font-semibold"><?= e(__('common.checkout')) ?></h1>
        <?php if (empty($items)): ?>
            <p class="mt-4 text-slate-500"><?= e(__('checkout.cart_empty')) ?></p>
        <?php else: ?>
            <form method="post" action="<?= e(url('/checkout')) ?>" class="mt-6 space-y-6">
                <?= csrf_field() ?>
                <div>
                    <h2 class="font-medium"><?= e(__('checkout.choose_gateway')) ?></h2>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach (($gateways ?? []) as $key => $g): ?>
                        <label class="flex items-center gap-2 px-3 py-2 rounded-md border border-slate-300 dark:border-slate-600 cursor-pointer hover:border-indigo-500">
                            <input type="radio" name="gateway" value="<?= e((string) $key) ?>" <?= $key === 'manual' ? 'checked' : '' ?>>
                            <span class="text-sm"><?= e(ucfirst((string) $key)) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 font-medium"><?= e(__('checkout.place_order')) ?></button>
            </form>
        <?php endif; ?>
    </div>
    <aside class="bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="font-medium"><?= e(__('cart.review_order')) ?></h2>
        <ul class="mt-4 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <?php foreach ($items as $item): ?>
                <li class="py-2 flex justify-between gap-3">
                    <span><?= e((string) ($item['title'] ?? $item['domain'] ?? 'Item')) ?> × <?= (int) ($item['years'] ?? 1) ?></span>
                    <span class="font-mono"><?= e(money((float) ($item['price'] ?? 0) * (int) ($item['years'] ?? 1), (string) ($item['currency'] ?? 'USD'))) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-3 flex justify-between font-semibold">
            <span><?= e(__('common.total')) ?></span>
            <span><?= e(money($subtotal, current_currency())) ?></span>
        </div>
    </aside>
</section>
