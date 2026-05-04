<?php
$user = auth_user();
$appName = (string) config('app.name', 'Reseller');
$themeMode = (string) (app()->session->get('theme_mode') ?? 'system');
$locale = current_locale();
$currency = current_currency();
$cart = (array) (app()->session->get('cart') ?? ['items' => []]);
$cartCount = count($cart['items'] ?? []);
?>
<header class="bg-white dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <button type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" id="mobile-menu-btn" aria-label="Toggle menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>
                <a href="<?= e(url('/')) ?>" class="text-lg font-semibold text-slate-900 dark:text-white"><?= e($appName) ?></a>
            </div>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="<?= e(url('/domains/search')) ?>" class="text-slate-700 dark:text-slate-200 hover:text-indigo-600"><?= e(__('common.search')) ?></a>
                <a href="<?= e(url('/domains/whois')) ?>" class="text-slate-700 dark:text-slate-200 hover:text-indigo-600">WHOIS</a>
                <a href="<?= e(url('/domains/transfer')) ?>" class="text-slate-700 dark:text-slate-200 hover:text-indigo-600"><?= e(__('common.transfer')) ?></a>
                <a href="<?= e(url('/blog')) ?>" class="text-slate-700 dark:text-slate-200 hover:text-indigo-600">Blog</a>
                <a href="<?= e(url('/kb')) ?>" class="text-slate-700 dark:text-slate-200 hover:text-indigo-600">KB</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="<?= e(url('/cart')) ?>" class="relative p-2 rounded-md text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="Cart">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-4 h-4 text-[10px] rounded-full bg-indigo-600 text-white"><?= (int) $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <!-- Theme toggle -->
                <a href="<?= e(url('/theme-mode/' . ($themeMode === 'dark' ? 'light' : 'dark'))) ?>" class="p-2 rounded-md text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="Toggle theme">
                    <?php if ($themeMode === 'dark'): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <?php endif; ?>
                </a>
                <!-- Language -->
                <details class="relative">
                    <summary class="list-none cursor-pointer p-2 rounded-md text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium uppercase"><?= e($locale) ?></summary>
                    <div class="absolute right-0 mt-2 w-32 rounded-md bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/10 overflow-hidden z-20">
                        <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/locale/en')) ?>">English</a>
                        <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/locale/bn')) ?>">বাংলা</a>
                    </div>
                </details>
                <?php if ($user): ?>
                    <details class="relative">
                        <summary class="list-none cursor-pointer flex items-center gap-2 p-2 rounded-md text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <span class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-semibold uppercase"><?= e(substr((string) ($user['name'] ?? 'U'), 0, 1)) ?></span>
                            <span class="hidden sm:inline text-sm"><?= e((string) ($user['name'] ?? '')) ?></span>
                        </summary>
                        <div class="absolute right-0 mt-2 w-48 rounded-md bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black/10 overflow-hidden z-20">
                            <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/account')) ?>"><?= e(__('common.dashboard')) ?></a>
                            <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/account/profile')) ?>"><?= e(__('account.profile')) ?></a>
                            <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/account/security')) ?>"><?= e(__('account.security')) ?></a>
                            <?php if (is_admin()): ?>
                                <a class="block px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700" href="<?= e(url('/admin')) ?>"><?= e(__('common.admin')) ?></a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/logout')) ?>" class="block">
                                <?= csrf_field() ?>
                                <button type="submit" class="w-full text-left px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30"><?= e(__('common.logout')) ?></button>
                            </form>
                        </div>
                    </details>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>" class="hidden sm:inline-block px-3 py-1.5 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"><?= e(__('common.login')) ?></a>
                    <a href="<?= e(url('/register')) ?>" class="px-3 py-1.5 rounded-md text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700"><?= e(__('common.register')) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <nav id="mobile-menu" class="md:hidden hidden border-t border-slate-200 dark:border-slate-700 py-2">
            <a href="<?= e(url('/domains/search')) ?>" class="block px-3 py-2 text-sm rounded hover:bg-slate-100 dark:hover:bg-slate-700"><?= e(__('common.search')) ?></a>
            <a href="<?= e(url('/domains/whois')) ?>" class="block px-3 py-2 text-sm rounded hover:bg-slate-100 dark:hover:bg-slate-700">WHOIS</a>
            <a href="<?= e(url('/domains/transfer')) ?>" class="block px-3 py-2 text-sm rounded hover:bg-slate-100 dark:hover:bg-slate-700"><?= e(__('common.transfer')) ?></a>
            <a href="<?= e(url('/blog')) ?>" class="block px-3 py-2 text-sm rounded hover:bg-slate-100 dark:hover:bg-slate-700">Blog</a>
            <a href="<?= e(url('/kb')) ?>" class="block px-3 py-2 text-sm rounded hover:bg-slate-100 dark:hover:bg-slate-700">KB</a>
        </nav>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    if (btn && menu) {
        btn.addEventListener('click', function () { menu.classList.toggle('hidden'); });
    }
});
</script>
