<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\HomepageSection;
use App\Models\TldPricing;

final class HomeController extends BaseController
{
    public function index(Request $request): Response
    {
        $tlds = TldPricing::where('is_active = 1 ORDER BY sort_order ASC, register_price ASC LIMIT 12');
        $sections = HomepageSection::where('is_active = 1 ORDER BY sort_order ASC');
        return $this->view('home/index', [
            'tlds' => $tlds,
            'sections' => $sections,
        ]);
    }

    public function switchLocale(Request $request, string $lang): Response
    {
        $supported = config('fonts.locales', ['en', 'bn']);
        if (in_array($lang, $supported, true)) {
            $this->app()->session->put('locale', $lang);
        }
        return $this->back();
    }

    public function switchCurrency(Request $request, string $code): Response
    {
        $code = strtoupper($code);
        if (preg_match('/^[A-Z]{3}$/', $code)) {
            $this->app()->session->put('currency', $code);
        }
        return $this->back();
    }

    public function switchThemeMode(Request $request, string $mode): Response
    {
        if (in_array($mode, ['light', 'dark', 'system', 'auto'], true)) {
            $this->app()->session->put('theme_mode', $mode);
        }
        return $this->back();
    }
}
