<?php layout('layouts/app', ['title' => __('admin.customers')]); ?>
<section class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <?= partial('admin-sidebar') ?>
    <div class="lg:col-span-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-6 overflow-x-auto">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold"><?= e(__('admin.customers')) ?></h1>
            <form method="get" action="<?= e(url('/admin/customers')) ?>" class="flex gap-2">
                <input name="q" value="<?= e((string) ($q ?? '')) ?>" placeholder="Search…" class="rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-1.5 text-sm" />
                <button type="submit" class="px-3 py-1.5 rounded-md bg-indigo-600 text-white text-sm">Go</button>
            </form>
        </div>
        <table class="mt-4 min-w-full text-sm divide-y divide-slate-200 dark:divide-slate-700">
            <thead><tr class="text-left">
                <th class="py-2 pr-4">ID</th><th class="py-2 pr-4">Name</th><th class="py-2 pr-4">Email</th><th class="py-2 pr-4">Role</th><th class="py-2 pr-4">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($pagination->items as $u): ?>
                <tr>
                    <td class="py-2 pr-4">#<?= (int) $u->id ?></td>
                    <td class="py-2 pr-4"><a class="text-indigo-600 hover:underline" href="<?= e(url('/admin/customers/' . (int) $u->id)) ?>"><?= e((string) ($u->name ?? '')) ?></a></td>
                    <td class="py-2 pr-4"><?= e((string) ($u->email ?? '')) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($u->role ?? 'customer')) ?></td>
                    <td class="py-2 pr-4"><?= e((string) ($u->status ?? 'active')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagination->items)): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No customers.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= partial('pagination', ['pagination' => $pagination]) ?>
    </div>
</section>
