<?php
/** @var string $content */
/** @var string|null $title */
$locale = current_locale();
$themeMode = (string) (app()->session->get('theme_mode') ?? 'system');
$dir = in_array($locale, ['ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr';
$bodyFont = $locale === 'bn' ? 'Tiro Bangla, Inter, system-ui, sans-serif' : 'Inter, system-ui, sans-serif';
$appName = (string) config('app.name', 'Reseller Platform');
?><!doctype html>
<html lang="<?= e($locale) ?>" dir="<?= e($dir) ?>" class="<?= $themeMode === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title><?= e($title ?? $appName) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Tiro+Bangla:ital@0;1&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        bangla: ['Tiro Bangla', 'Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
        // Auto-apply dark mode if preference is "system"
        (function () {
            var pref = '<?= e($themeMode) ?>';
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (pref === 'system' && prefersDark) {
                document.documentElement.classList.add('dark');
            } else if (pref === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        body { font-family: <?= $bodyFont ?>; }
        .dark body { background: #0b1220; color: #e6e9ef; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 antialiased">

<?= partial('header') ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php if ($flash = flash_get('success')): ?>
        <div class="mb-4 rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-emerald-800 dark:text-emerald-200"><?= e((string) $flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = flash_get('error')): ?>
        <div class="mb-4 rounded-md bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 px-4 py-3 text-rose-800 dark:text-rose-200"><?= e((string) $flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = flash_get('info')): ?>
        <div class="mb-4 rounded-md bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 px-4 py-3 text-sky-800 dark:text-sky-200"><?= e((string) $flash) ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>

<?= partial('footer') ?>

</body>
</html>
