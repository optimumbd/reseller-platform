<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\SiteSetting;

final class SettingsController extends BaseController
{
    public function index(Request $request): Response
    {
        $rows = $this->app()->db->select('SELECT `key`, `value` FROM site_settings');
        $bag = [];
        foreach ($rows as $r) {
            $bag[(string) $r['key']] = $r['value'];
        }
        return $this->view('admin/settings/index', ['settings' => $bag]);
    }

    public function update(Request $request): Response
    {
        $payload = (array) $request->input('settings', []);
        foreach ($payload as $key => $value) {
            SiteSetting::set((string) $key, $value);
        }
        flash('success', __('admin.settings_saved'));
        return $this->back();
    }
}
