<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\TldPricing;

final class TldPricingController extends BaseController
{
    public function index(Request $request): Response
    {
        $rows = TldPricing::all('sort_order ASC, tld ASC', 5000);
        return $this->view('admin/tld-pricing/index', ['rows' => $rows]);
    }

    public function update(Request $request): Response
    {
        foreach ((array) $request->input('rows', []) as $id => $vals) {
            $row = TldPricing::find((int) $id);
            if (!$row) {
                continue;
            }
            foreach (['register_price', 'renew_price', 'transfer_price', 'currency', 'is_active', 'sort_order'] as $f) {
                if (array_key_exists($f, $vals)) {
                    $row->$f = $vals[$f];
                }
            }
            $row->save();
        }
        flash('success', __('admin.pricing_saved'));
        return $this->back();
    }

    public function create(Request $request): Response
    {
        $row = new TldPricing([
            'tld' => strtolower(trim((string) $request->input('tld'))),
            'register_price' => (float) $request->input('register_price', 0),
            'renew_price' => (float) $request->input('renew_price', 0),
            'transfer_price' => (float) $request->input('transfer_price', 0),
            'currency' => strtoupper((string) $request->input('currency', 'USD')),
            'is_active' => 1,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);
        $row->save();
        return $this->back();
    }
}
