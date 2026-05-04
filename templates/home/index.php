<?php layout('layouts/app', ['title' => config('app.name', 'Reseller Platform')]); ?>

<section class="bg-gradient-to-br from-indigo-600 to-violet-700 dark:from-indigo-700 dark:to-violet-900 text-white rounded-xl px-6 sm:px-10 py-10 sm:py-16 shadow-lg">
    <div class="max-w-3xl">
        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">Find your perfect domain</h1>
        <p class="mt-3 text-indigo-100 text-base sm:text-lg">Register, transfer, and manage domains with one of the world's most flexible reseller platforms.</p>
        <form method="get" action="<?= e(url('/domains/search')) ?>" class="mt-6 flex flex-col sm:flex-row gap-2">
            <input type="text" name="q" placeholder="Search any domain (e.g. mybrand.com)" class="flex-1 rounded-md border-0 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-white/60" />
            <button type="submit" class="px-5 py-3 rounded-md bg-white text-indigo-700 font-semibold hover:bg-indigo-50"><?= e(__('common.search')) ?></button>
        </form>
        <div class="mt-4 flex flex-wrap gap-2 text-xs text-indigo-100">
            <?php foreach (['.com', '.net', '.org', '.io', '.dev', '.app', '.bd', '.com.bd'] as $tldChip): ?>
                <span class="px-2 py-1 rounded bg-white/10 backdrop-blur"><?= e($tldChip) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $cards = [
        ['title' => 'Domain registration', 'desc' => '500+ TLDs at competitive prices.', 'href' => url('/domains/search'), 'icon' => '🌐'],
        ['title' => 'Easy transfer', 'desc' => 'Move your domain in minutes.', 'href' => url('/domains/transfer'), 'icon' => '↪️'],
        ['title' => 'WHOIS lookup', 'desc' => 'Free public WHOIS &amp; DNS tools.', 'href' => url('/domains/whois'), 'icon' => '🔎'],
        ['title' => 'Hosting &amp; SSL', 'desc' => 'Hosting, email, SSL — coming soon.', 'href' => '#', 'icon' => '⚡'],
    ];
    foreach ($cards as $c): ?>
        <a href="<?= e($c['href']) ?>" class="block rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition">
            <div class="text-3xl"><?= e($c['icon']) ?></div>
            <div class="mt-2 font-semibold text-slate-900 dark:text-white"><?= e($c['title']) ?></div>
            <div class="text-sm text-slate-600 dark:text-slate-300"><?= $c['desc'] ?></div>
        </a>
    <?php endforeach; ?>
</section>

<section class="mt-12">
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Featured TLD pricing</h2>
    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/40">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-700 dark:text-slate-200">TLD</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-700 dark:text-slate-200">Register</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-700 dark:text-slate-200">Renew</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-700 dark:text-slate-200">Transfer</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach (($tlds ?? []) as $row): ?>
                <tr>
                    <td class="px-4 py-2 font-mono"><?= e('.' . ltrim((string) $row->tld, '.')) ?></td>
                    <td class="px-4 py-2"><?= e(money((float) $row->register_price, (string) $row->currency)) ?></td>
                    <td class="px-4 py-2"><?= e(money((float) $row->renew_price, (string) $row->currency)) ?></td>
                    <td class="px-4 py-2"><?= e(money((float) $row->transfer_price, (string) $row->currency)) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tlds)): ?>
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Pricing will appear here once configured in admin.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
