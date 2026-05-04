<?php
$appName = (string) config('app.name', 'Reseller Platform');
?>
<footer class="mt-12 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-4 gap-6 text-sm">
        <div>
            <div class="font-semibold text-slate-900 dark:text-white"><?= e($appName) ?></div>
            <p class="mt-2 text-slate-600 dark:text-slate-300">A complete domain &amp; hosting reseller platform.</p>
        </div>
        <div>
            <h4 class="font-medium text-slate-900 dark:text-white">Products</h4>
            <ul class="mt-2 space-y-1 text-slate-600 dark:text-slate-300">
                <li><a href="<?= e(url('/domains/search')) ?>" class="hover:text-indigo-600">Domain registration</a></li>
                <li><a href="<?= e(url('/domains/transfer')) ?>" class="hover:text-indigo-600">Domain transfer</a></li>
                <li><a href="<?= e(url('/domains/whois')) ?>" class="hover:text-indigo-600">WHOIS lookup</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-medium text-slate-900 dark:text-white">Resources</h4>
            <ul class="mt-2 space-y-1 text-slate-600 dark:text-slate-300">
                <li><a href="<?= e(url('/blog')) ?>" class="hover:text-indigo-600">Blog</a></li>
                <li><a href="<?= e(url('/kb')) ?>" class="hover:text-indigo-600">Knowledge base</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-medium text-slate-900 dark:text-white">Account</h4>
            <ul class="mt-2 space-y-1 text-slate-600 dark:text-slate-300">
                <li><a href="<?= e(url('/login')) ?>" class="hover:text-indigo-600">Login</a></li>
                <li><a href="<?= e(url('/register')) ?>" class="hover:text-indigo-600">Register</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-200 dark:border-slate-700 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
        © <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.
    </div>
</footer>
