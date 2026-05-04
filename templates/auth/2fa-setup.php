<?php layout('layouts/app', ['title' => 'Set up two-factor authentication']); ?>
<?php
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode((string) ($otpauth ?? ''));
?>
<div class="max-w-xl mx-auto bg-white dark:bg-slate-800 rounded-lg shadow border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
    <h1 class="text-2xl font-semibold">Set up two-factor authentication</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Scan this QR code with Google Authenticator, Authy, 1Password, or any TOTP app, then enter the 6-digit code below.</p>
    <div class="mt-4 flex flex-col sm:flex-row gap-6 items-center">
        <img src="<?= e($qrUrl) ?>" alt="2FA QR" class="rounded-md bg-white p-2 ring-1 ring-slate-200" width="200" height="200" />
        <div>
            <div class="text-xs text-slate-500">Secret (manual entry):</div>
            <code class="block mt-1 px-2 py-1 rounded bg-slate-100 dark:bg-slate-900 text-sm break-all"><?= e((string) ($secret ?? '')) ?></code>
        </div>
    </div>
    <form method="post" action="<?= e(url('/account/security/2fa/enable')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="secret" value="<?= e((string) ($secret ?? '')) ?>">
        <input name="code" inputmode="numeric" pattern="[0-9]*" minlength="6" maxlength="8" required placeholder="123 456" class="block w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-3 text-center text-xl tracking-widest" />
        <button type="submit" class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white py-2 font-medium">Enable 2FA</button>
    </form>
</div>
