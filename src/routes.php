<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Application Routes
 |--------------------------------------------------------------------------
 | $router is bound to App\Core\Router (from App::registerRoutes()).
 */

use App\Controllers\Account\AccountController;
use App\Controllers\Account\DnsController as UserDnsController;
use App\Controllers\Account\NameserverController;
use App\Controllers\Account\OrderController as UserOrderController;
use App\Controllers\Account\PrivacyController;
use App\Controllers\Account\RenewalController;
use App\Controllers\Account\ServiceController;
use App\Controllers\Account\TicketController as UserTicketController;
use App\Controllers\Account\VerificationController;
use App\Controllers\Account\WalletController;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AnnouncementController;
use App\Controllers\Admin\AppearanceController;
use App\Controllers\Admin\CouponController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DomainController as AdminDomainController;
use App\Controllers\Admin\EmailTemplateController;
use App\Controllers\Admin\HomepageController;
use App\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Controllers\Admin\LanguageController;
use App\Controllers\Admin\ModeratorController;
use App\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Controllers\Admin\PricingController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\RegistrarController as AdminRegistrarController;
use App\Controllers\Admin\SeoController;
use App\Controllers\Admin\ServiceController as AdminServiceController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ThemeController as AdminThemeController;
use App\Controllers\Admin\TicketController as AdminTicketController;
use App\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Controllers\Api\V1\DomainController as ApiDomainController;
use App\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Controllers\Api\V1\ServiceController as ApiServiceController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\DomainController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\InvoiceController;
use App\Controllers\PageController;
use App\Controllers\SocialAuthController;
use App\Controllers\TransferController;
use App\Controllers\TwoFactorController;
use App\Controllers\WebhookController;
use App\Controllers\Reseller\DashboardController as ResellerDashboardController;

/** @var \App\Core\Router $router */

// ───────────── Install Wizard (only available before install.lock exists) ─────────────
$router->get('/install', [InstallController::class, 'show']);
$router->post('/install', [InstallController::class, 'run']);
$router->get('/install/complete', [InstallController::class, 'complete']);

// ───────────── Public ─────────────
$router->get('/', [HomeController::class, 'index'])->name('home');
$router->get('/locale/{lang}', [HomeController::class, 'switchLocale'])->name('locale.switch');
$router->get('/currency/{code}', [HomeController::class, 'switchCurrency'])->name('currency.switch');
$router->get('/theme-mode/{mode}', [HomeController::class, 'switchThemeMode'])->name('theme.mode');

$router->get('/domains/search', [DomainController::class, 'search'])->name('domains.search');
$router->post('/domains/search', [DomainController::class, 'search']);
$router->get('/domains/whois', [DomainController::class, 'whois'])->name('domains.whois');
$router->get('/domains/dns-lookup', [DomainController::class, 'dnsLookup'])->name('domains.dns_lookup');
$router->get('/domains/generator', [DomainController::class, 'generator'])->name('domains.generator');
$router->get('/domains/transfer', [TransferController::class, 'show'])->name('domains.transfer');
$router->post('/domains/transfer', [TransferController::class, 'submit']);

$router->get('/cart', [CartController::class, 'show'])->name('cart.show');
$router->post('/cart/add', [CartController::class, 'add'])->name('cart.add');
$router->post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
$router->post('/cart/update', [CartController::class, 'update'])->name('cart.update');
$router->post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

$router->get('/checkout', [CheckoutController::class, 'show'])->middleware('auth')->name('checkout.show');
$router->post('/checkout', [CheckoutController::class, 'process'])->middleware(['auth', 'csrf']);
$router->get('/checkout/return', [CheckoutController::class, 'returnUrl'])->name('checkout.return');
$router->get('/checkout/cancel', [CheckoutController::class, 'cancelUrl'])->name('checkout.cancel');

$router->get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
$router->get('/blog', [PageController::class, 'blog'])->name('blog.index');
$router->get('/blog/{slug}', [PageController::class, 'blogPost'])->name('blog.show');
$router->get('/kb', [PageController::class, 'kb'])->name('kb.index');
$router->get('/kb/{slug}', [PageController::class, 'kbArticle'])->name('kb.show');
$router->get('/status', [PageController::class, 'status'])->name('status');

// ───────────── Auth (guests) ─────────────
$router->group(['middleware' => ['guest']], function ($r) {
    $r->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $r->post('/login', [AuthController::class, 'login'])->middleware(['csrf', 'throttle']);
    $r->get('/register', [AuthController::class, 'showRegister'])->name('register');
    $r->post('/register', [AuthController::class, 'register'])->middleware(['csrf', 'throttle']);
    $r->get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.forgot');
    $r->post('/forgot-password', [AuthController::class, 'forgot'])->middleware(['csrf', 'throttle']);
    $r->get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    $r->post('/reset-password', [AuthController::class, 'reset'])->middleware('csrf');
    $r->get('/magic-link', [AuthController::class, 'showMagic'])->name('login.magic');
    $r->post('/magic-link', [AuthController::class, 'sendMagic'])->middleware(['csrf', 'throttle']);
    $r->get('/magic-link/verify/{token}', [AuthController::class, 'verifyMagic']);
    // Social
    $r->get('/auth/{provider}', [SocialAuthController::class, 'redirect']);
    $r->get('/auth/{provider}/callback', [SocialAuthController::class, 'callback']);
});

$router->get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('email.verify');
$router->post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'csrf'])->name('logout');

// 2FA challenge after password but before session activation
$router->get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
$router->post('/two-factor/verify', [TwoFactorController::class, 'verify'])->middleware('csrf');

// ───────────── Account (authenticated users) ─────────────
$router->group(['prefix' => '/account', 'middleware' => ['auth']], function ($r) {
    $r->get('', [AccountController::class, 'dashboard'])->name('account.dashboard');
    $r->get('/profile', [AccountController::class, 'profile'])->name('account.profile');
    $r->post('/profile', [AccountController::class, 'updateProfile'])->middleware('csrf');
    $r->get('/security', [AccountController::class, 'security'])->name('account.security');
    $r->post('/security/password', [AccountController::class, 'updatePassword'])->middleware('csrf');
    $r->get('/security/2fa', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    $r->post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('csrf');
    $r->post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('csrf');
    $r->get('/security/api-tokens', [AccountController::class, 'apiTokens'])->name('account.api_tokens');
    $r->post('/security/api-tokens', [AccountController::class, 'createApiToken'])->middleware('csrf');
    $r->post('/security/api-tokens/{id}/revoke', [AccountController::class, 'revokeApiToken'])->middleware('csrf');

    $r->get('/services', [ServiceController::class, 'index'])->name('account.services');
    $r->get('/services/{id}', [ServiceController::class, 'show'])->name('account.service');

    $r->get('/domains', [ServiceController::class, 'domains'])->name('account.domains');
    $r->get('/domains/{id}/nameservers', [NameserverController::class, 'index'])->name('account.nameservers');
    $r->post('/domains/{id}/nameservers', [NameserverController::class, 'update'])->middleware('csrf');
    $r->get('/domains/{id}/dns', [UserDnsController::class, 'index'])->name('account.dns');
    $r->post('/domains/{id}/dns', [UserDnsController::class, 'store'])->middleware('csrf');
    $r->post('/domains/{id}/dns/{record}', [UserDnsController::class, 'update'])->middleware('csrf');
    $r->post('/domains/{id}/dns/{record}/delete', [UserDnsController::class, 'destroy'])->middleware('csrf');
    $r->post('/domains/{id}/privacy', [PrivacyController::class, 'toggle'])->middleware('csrf');
    $r->post('/domains/{id}/lock', [PrivacyController::class, 'lock'])->middleware('csrf');
    $r->post('/domains/{id}/auto-renew', [RenewalController::class, 'toggleAutoRenew'])->middleware('csrf');
    $r->post('/domains/{id}/renew', [RenewalController::class, 'renew'])->middleware('csrf');
    $r->post('/domains/{id}/epp', [PrivacyController::class, 'eppCode'])->middleware('csrf');

    $r->get('/orders', [UserOrderController::class, 'index'])->name('account.orders');
    $r->get('/orders/{id}', [UserOrderController::class, 'show'])->name('account.order');
    $r->get('/invoices', [InvoiceController::class, 'index'])->name('account.invoices');
    $r->get('/invoices/{id}', [InvoiceController::class, 'show'])->name('account.invoice');
    $r->get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf'])->name('account.invoice.pdf');

    $r->get('/wallet', [WalletController::class, 'index'])->name('account.wallet');
    $r->post('/wallet/topup', [WalletController::class, 'topup'])->middleware('csrf');

    $r->get('/verification', [VerificationController::class, 'show'])->name('account.verification');
    $r->post('/verification', [VerificationController::class, 'submit'])->middleware('csrf');

    $r->get('/tickets', [UserTicketController::class, 'index'])->name('account.tickets');
    $r->get('/tickets/new', [UserTicketController::class, 'create'])->name('account.tickets.new');
    $r->post('/tickets', [UserTicketController::class, 'store'])->middleware('csrf');
    $r->get('/tickets/{id}', [UserTicketController::class, 'show'])->name('account.ticket');
    $r->post('/tickets/{id}/reply', [UserTicketController::class, 'reply'])->middleware('csrf');
});

// ───────────── Reseller portal ─────────────
$router->group(['prefix' => '/reseller', 'middleware' => ['auth', 'reseller']], function ($r) {
    $r->get('', [ResellerDashboardController::class, 'index'])->name('reseller.dashboard');
});

// ───────────── Admin ─────────────
$router->get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
$router->post('/admin/login', [AdminAuthController::class, 'login'])->middleware(['csrf', 'throttle']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware(['auth', 'csrf'])->name('admin.logout');

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function ($r) {
    $r->get('', [DashboardController::class, 'index'])->name('admin.dashboard');
    $r->get('/customers', [CustomerController::class, 'index'])->name('admin.customers');
    $r->get('/customers/{id}', [CustomerController::class, 'show'])->name('admin.customer');
    $r->get('/domains', [AdminDomainController::class, 'index'])->name('admin.domains');
    $r->get('/services', [AdminServiceController::class, 'index'])->name('admin.services');
    $r->get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders');
    $r->get('/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.order');
    $r->get('/invoices', [AdminInvoiceController::class, 'index'])->name('admin.invoices');
    $r->get('/products', [AdminProductController::class, 'index'])->name('admin.products');
    $r->get('/pricing', [PricingController::class, 'index'])->name('admin.pricing');
    $r->get('/coupons', [CouponController::class, 'index'])->name('admin.coupons');
    $r->get('/verifications', [AdminVerificationController::class, 'index'])->name('admin.verifications');
    $r->post('/verifications/{id}/approve', [AdminVerificationController::class, 'approve'])->middleware('csrf');
    $r->post('/verifications/{id}/reject', [AdminVerificationController::class, 'reject'])->middleware('csrf');
    $r->get('/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets');
    $r->get('/tickets/{id}', [AdminTicketController::class, 'show'])->name('admin.ticket');
    $r->get('/registrars', [AdminRegistrarController::class, 'index'])->name('admin.registrars');
    $r->get('/payments', [AdminPaymentController::class, 'index'])->name('admin.payments');
    $r->get('/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications');
    $r->get('/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements');
    $r->get('/email-templates', [EmailTemplateController::class, 'index'])->name('admin.emails');
    $r->get('/languages', [LanguageController::class, 'index'])->name('admin.languages');
    $r->get('/themes', [AdminThemeController::class, 'index'])->name('admin.themes');
    $r->get('/appearance', [AppearanceController::class, 'index'])->name('admin.appearance');
    $r->get('/seo', [SeoController::class, 'index'])->name('admin.seo');
    $r->get('/homepage', [HomepageController::class, 'index'])->name('admin.homepage');
    $r->post('/homepage', [HomepageController::class, 'update'])->middleware('csrf');
    $r->get('/moderators', [ModeratorController::class, 'index'])->name('admin.moderators');
    $r->get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
    $r->post('/settings', [SettingsController::class, 'update'])->middleware('csrf');
});

// ───────────── Public REST API V1 ─────────────
$router->group(['prefix' => '/api/v1', 'middleware' => ['api', 'cors']], function ($r) {
    $r->get('/domains/check', [ApiDomainController::class, 'check']);
    $r->post('/domains/register', [ApiDomainController::class, 'register']);
    $r->get('/services', [ApiServiceController::class, 'index']);
    $r->get('/services/{id}', [ApiServiceController::class, 'show']);
    $r->get('/orders', [ApiOrderController::class, 'index']);
});

// ───────────── Webhooks ─────────────
$router->post('/webhook/{gateway}', [WebhookController::class, 'handle'])->middleware('webhook.sig');

// ───────────── Health ─────────────
$router->get('/health', function () {
    return \App\Core\Response::json(['status' => 'ok', 'time' => time()]);
});
