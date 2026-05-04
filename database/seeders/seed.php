<?php

declare(strict_types=1);

use App\Core\App;

/** @var App $app */
$app = App::getInstance();
$db = $app->db;

// Seed TLD pricing if empty
$existing = (int) $db->scalar('SELECT COUNT(*) FROM tld_pricing');
if ($existing === 0) {
    $tlds = [
        ['com', 9.99, 12.99, 9.99],
        ['net', 11.99, 13.99, 11.99],
        ['org', 10.99, 12.99, 10.99],
        ['io',  39.99, 42.99, 39.99],
        ['dev', 14.99, 16.99, 14.99],
        ['app', 16.99, 18.99, 16.99],
        ['xyz',  1.99,  9.99,  1.99],
        ['info', 3.99, 19.99,  3.99],
        ['biz',  4.99, 14.99,  4.99],
        ['com.bd', 1500.00, 1500.00, 1500.00],
        ['bd',     2500.00, 2500.00, 2500.00],
    ];
    $sortOrder = 1;
    foreach ($tlds as [$tld, $reg, $renew, $transfer]) {
        $currency = str_ends_with($tld, 'bd') ? 'BDT' : 'USD';
        $db->insert('tld_pricing', [
            'tld' => $tld,
            'register_price' => $reg,
            'renew_price' => $renew,
            'transfer_price' => $transfer,
            'currency' => $currency,
            'is_active' => 1,
            'sort_order' => $sortOrder++,
        ]);
    }
    fwrite(STDOUT, "Seeded TLD pricing." . PHP_EOL);
}

// Seed default site_settings keys if not present
$defaults = [
    'site_name' => $app->config('app.name', 'Reseller Platform'),
    'support_email' => $app->config('mail.from_address', 'support@example.com'),
    'default_currency' => 'USD',
    'default_locale' => 'en',
    'tax_rate' => '0',
    'company_address' => '',
];
foreach ($defaults as $key => $value) {
    // site_settings.key IS the primary key — no `id` column exists.
    $row = $db->selectOne('SELECT `key` FROM site_settings WHERE `key` = ?', [$key]);
    if (!$row) {
        $db->insert('site_settings', ['key' => $key, 'value' => $value]);
    }
}

// Seed homepage sections — schema is (key, title, content JSON, is_active, sort_order)
$existingSections = (int) $db->scalar('SELECT COUNT(*) FROM homepage_sections');
if ($existingSections === 0) {
    $db->insert('homepage_sections', [
        'key' => 'hero',
        'title' => 'Find your perfect domain',
        'content' => json_encode([
            'subtitle' => 'Register, transfer, and manage domains with ease.',
            'cta_label' => 'Search domains',
            'cta_href' => '/domains/search',
        ], JSON_UNESCAPED_UNICODE),
        'sort_order' => 1,
        'is_active' => 1,
    ]);
}

fwrite(STDOUT, "Seeders complete." . PHP_EOL);
