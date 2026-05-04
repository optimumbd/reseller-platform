<?php layout('layouts/app', ['title' => 'Ticket']); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('account-sidebar') ?>
    <div class="lg:col-span-3 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6">
            <h1 class="text-xl font-semibold"><?= e((string) ($ticket->subject ?? '')) ?></h1>
            <p class="text-sm text-slate-500"><?= e((string) ($ticket->number ?? $ticket->id)) ?> · <?= e((string) ($ticket->status ?? '')) ?></p>
        </div>
        <?php foreach (($replies ?? []) as $r): ?>
            <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5 <?= !empty($r->is_staff) ? 'border-indigo-300 dark:border-indigo-700' : '' ?>">
                <div class="text-xs text-slate-500"><?= e((string) ($r->created_at ?? '')) ?> · <?= !empty($r->is_staff) ? 'staff' : 'you' ?></div>
                <div class="mt-2 whitespace-pre-line"><?= e((string) ($r->body ?? '')) ?></div>
            </div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('/account/tickets/' . (int) $ticket->id . '/reply')) ?>" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-5">
            <?= csrf_field() ?>
            <textarea name="message" required rows="4" placeholder="Reply..." class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2"></textarea>
            <button type="submit" class="mt-2 px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Reply</button>
        </form>
    </div>
</section>
