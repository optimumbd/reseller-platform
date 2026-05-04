<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Application Routes
 |--------------------------------------------------------------------------
 | $router is bound to App\Core\Router (from App::registerRoutes()).
 */

use App\Controllers\Account\DashboardController as AccountDashboard;
use App\Controllers\Account\DomainsController as AccountDomains;
use App\Controllers\Account\InvoicesController as AccountInvoices;
use App\Controllers\Account\OrdersController as AccountOrders;
use App\Controllers\Account\ProfileController as AccountProfile;
use App\Controllers\Account\ServicesController as AccountServices;
use App\Controllers\Account\TicketsController as AccountTickets;
use App\Controllers\Account\WalletController as AccountWallet;
use App\Controllers\Admin\CustomersController as AdminCustomers;
use App\Controllers\Admin\DashboardController as AdminDashboard;
use App\Controllers\Admin\DomainsController as AdminDomains;
use App\Controllers\Admin\InvoicesController as AdminInvoices;
use App\Controllers\Admin\OrdersController as AdminOrders;
use App\Controllers\Admin\ServicesController as AdminServices;
use App\Controllers\Admin\SettingsController as AdminSettings;
use App\Controllers\Admin\TldPricingController as AdminTldPricing;
use App\Controllers\Api\V1\DomainController as ApiDomainController;
use App\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Controllers\Api\V1\ServiceController as ApiServiceController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\DomainController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\PageController;
use App\Controllers\TwoFactorController;
use App\Controllers\WebhookController;

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
$router->get('/domains/transfer', [DomainController::class, 'transferIndex'])->name('domains.transfer');

$router->get('/cart', [CartController::class, 'index'])->name('cart.show');
$router->post('/cart/add', [CartController::class, 'add'])->name('cart.add');
$router->post('/cart/remove/{index}', [CartController::class, 'remove'])->name('cart.remove');
$router->post('/cart/update', [CartController::class, 'update'])->name('cart.update');
$router->post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');

$router->get('/checkout', [CheckoutController::class, 'index'])->middleware('auth')->name('checkout.show');
$router->post('/checkout', [CheckoutController::class, 'place'])->middleware(['auth', 'csrf']);

$router->get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
$router->get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
$router->get('/blog/{slug}', [PageController::class, 'blogPost'])->name('blog.show');
$router->get('/kb', [PageController::class, 'kbIndex'])->name('kb.index');
$router->get('/kb/{slug}', [PageController::class, 'kbArticle'])->name('kb.show');

// ───────────── Auth (guests) ─────────────
$router->group(['middleware' => ['guest']], function ($r) {
    $r->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $r->post('/login', [AuthController::class, 'login'])->middleware(['csrf']);
    $r->get('/register', [AuthController::class, 'showRegister'])->name('register');
    $r->post('/register', [AuthController::class, 'register'])->middleware(['csrf']);
    $r->get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.forgot');
    $r->post('/forgot-password', [AuthController::class, 'forgot'])->middleware(['csrf']);
    $r->get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    $r->post('/reset-password', [AuthController::class, 'reset'])->middleware('csrf');
    $r->get('/magic-link', [AuthController::class, 'showMagic'])->name('login.magic');
    $r->post('/magic-link', [AuthController::class, 'sendMagic'])->middleware(['csrf']);
    $r->get('/magic-link/verify/{token}', [AuthController::class, 'verifyMagic']);
});

$router->get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('email.verify');
$router->post('/logout', [AuthController::class, 'logout'])->middleware(['auth', 'csrf'])->name('logout');

// 2FA challenge after password but before session activation
$router->get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
$router->post('/two-factor/verify', [TwoFactorController::class, 'verify'])->middleware('csrf');

// ───────────── Account (authenticated users) ─────────────
$router->group(['prefix' => '/account', 'middleware' => ['auth']], function ($r) {
    $r->get('', [AccountDashboard::class, 'index'])->name('account.dashboard');
    $r->get('/profile', [AccountProfile::class, 'edit'])->name('account.profile');
    $r->post('/profile', [AccountProfile::class, 'update'])->middleware('csrf');
    $r->get('/security', [AccountProfile::class, 'security'])->name('account.security');
    $r->post('/security/password', [AccountProfile::class, 'changePassword'])->middleware('csrf');
    $r->get('/security/2fa', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    $r->post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('csrf');
    $r->post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->middleware('csrf');

    $r->get('/services', [AccountServices::class, 'index'])->name('account.services');
    $r->get('/services/{id}', [AccountServices::class, 'show'])->name('account.service');

    $r->get('/domains', [AccountDomains::class, 'index'])->name('account.domains');
    $r->get('/domains/{id}', [AccountDomains::class, 'show'])->name('account.domain');
    $r->get('/domains/{id}/nameservers', [AccountDomains::class, 'nameservers'])->name('account.nameservers');
    $r->post('/domains/{id}/nameservers', [AccountDomains::class, 'nameservers'])->middleware('csrf');
    $r->post('/domains/{id}/lock', [AccountDomains::class, 'lock'])->middleware('csrf');
    $r->post('/domains/{id}/privacy', [AccountDomains::class, 'privacy'])->middleware('csrf');
    $r->post('/domains/{id}/auto-renew', [AccountDomains::class, 'autoRenew'])->middleware('csrf');

    $r->get('/orders', [AccountOrders::class, 'index'])->name('account.orders');
    $r->get('/orders/{id}', [AccountOrders::class, 'show'])->name('account.order');
    $r->get('/invoices', [AccountInvoices::class, 'index'])->name('account.invoices');
    $r->get('/invoices/{id}', [AccountInvoices::class, 'show'])->name('account.invoice');
    $r->post('/invoices/{id}/pay', [AccountInvoices::class, 'pay'])->middleware('csrf')->name('account.invoice.pay');

    $r->get('/wallet', [AccountWallet::class, 'index'])->name('account.wallet');

    $r->get('/tickets', [AccountTickets::class, 'index'])->name('account.tickets');
    $r->get('/tickets/new', [AccountTickets::class, 'create'])->name('account.tickets.new');
    $r->post('/tickets', [AccountTickets::class, 'store'])->middleware('csrf');
    $r->get('/tickets/{id}', [AccountTickets::class, 'show'])->name('account.ticket');
    $r->post('/tickets/{id}/reply', [AccountTickets::class, 'reply'])->middleware('csrf');
});

// ───────────── Admin ─────────────
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function ($r) {
    $r->get('', [AdminDashboard::class, 'index'])->name('admin.dashboard');
    $r->get('/customers', [AdminCustomers::class, 'index'])->name('admin.customers');
    $r->get('/customers/{id}', [AdminCustomers::class, 'show'])->name('admin.customer');
    $r->post('/customers/{id}', [AdminCustomers::class, 'update'])->middleware('csrf');
    $r->get('/domains', [AdminDomains::class, 'index'])->name('admin.domains');
    $r->get('/services', [AdminServices::class, 'index'])->name('admin.services');
    $r->get('/orders', [AdminOrders::class, 'index'])->name('admin.orders');
    $r->get('/orders/{id}', [AdminOrders::class, 'show'])->name('admin.order');
    $r->get('/invoices', [AdminInvoices::class, 'index'])->name('admin.invoices');
    $r->get('/invoices/{id}', [AdminInvoices::class, 'show'])->name('admin.invoice');
    $r->get('/pricing', [AdminTldPricing::class, 'index'])->name('admin.pricing');
    $r->post('/pricing', [AdminTldPricing::class, 'update'])->middleware('csrf');
    $r->post('/pricing/create', [AdminTldPricing::class, 'create'])->middleware('csrf');
    $r->get('/settings', [AdminSettings::class, 'index'])->name('admin.settings');
    $r->post('/settings', [AdminSettings::class, 'update'])->middleware('csrf');
});

// ───────────── Public REST API V1 ─────────────
$router->group(['prefix' => '/api/v1', 'middleware' => ['cors']], function ($r) {
    $r->get('/domains/check', [ApiDomainController::class, 'check']);
    $r->get('/services', [ApiServiceController::class, 'index'])->middleware('api');
    $r->get('/orders', [ApiOrderController::class, 'index'])->middleware('api');
});

// ───────────── Webhooks ─────────────
$router->post('/webhook/{gateway}', [WebhookController::class, 'handle']);

// ───────────── Health ─────────────
$router->get('/health', [HealthController::class, 'check']);
