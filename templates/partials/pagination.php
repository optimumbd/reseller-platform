<?php
/** @var \App\Core\Pagination $pagination */
if (!isset($pagination) || !$pagination instanceof \App\Core\Pagination) {
    return;
}
$lastPage = $pagination->totalPages();
if ($lastPage <= 1) {
    return;
}
$base = $pagination->baseUrl ?: '';
$separator = str_contains($base, '?') ? '&' : '?';
?>
<nav class="mt-4 flex items-center justify-between text-sm">
    <div class="text-slate-500">Page <?= (int) $pagination->page ?> of <?= (int) $lastPage ?></div>
    <div class="flex gap-1">
        <?php for ($p = max(1, $pagination->page - 3); $p <= min($lastPage, $pagination->page + 3); $p++): ?>
            <a href="<?= e($base . $separator . 'page=' . $p) ?>" class="px-2 py-1 rounded <?= $p === $pagination->page ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700' ?>"><?= (int) $p ?></a>
        <?php endfor; ?>
    </div>
</nav>
