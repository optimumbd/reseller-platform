<?php layout('layouts/app', ['title' => __('admin.dashboard')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <?php foreach ([
                ['Customers', $stats['users'] ?? 0, '👥'],
                ['Domains', $stats['domains'] ?? 0, '🌐'],
                ['Services', $stats['services'] ?? 0, '📦'],
                ['Open tickets', $stats['open_tickets'] ?? 0, '🎫'],
                ['Unpaid invoices', $stats['unpaid_invoices'] ?? 0, '💳'],
                ['Orders today', $stats['orders_today'] ?? 0, '🧾'],
            ] as $card): ?>
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
                    <div class="text-xl"><?= e($card[2]) ?></div>
                    <div class="mt-1 text-xs text-slate-500"><?= e($card[0]) ?></div>
                    <div class="text-2xl font-semibold"><?= (int) $card[1] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="font-medium">Recent orders</h2>
            <ul class="mt-3 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                <?php foreach (($recentOrders ?? []) as $o): ?>
                    <li class="py-2 flex items-center justify-between">
                        <a class="text-indigo-600 hover:underline" href="<?= e(url('/admin/orders/' . (int) $o->id)) ?>">#<?= e((string) ($o->number ?? $o->id)) ?></a>
                        <span class="font-mono"><?= e(money((float) ($o->total ?? 0), (string) ($o->currency ?? 'USD'))) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
            <h2 class="font-medium">Recent customers</h2>
            <ul class="mt-3 divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                <?php foreach (($recentUsers ?? []) as $u): ?>
                    <li class="py-2 flex items-center justify-between">
                        <a class="text-indigo-600 hover:underline" href="<?= e(url('/admin/customers/' . (int) $u->id)) ?>"><?= e((string) ($u->name ?? '')) ?> <span class="text-slate-500 text-xs">(<?= e((string) ($u->email ?? '')) ?>)</span></a>
                        <span class="text-xs"><?= e((string) ($u->created_at ?? '')) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
