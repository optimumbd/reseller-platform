<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

final class ProfileController extends BaseController
{
    public function edit(Request $request): Response
    {
        return $this->view('account/profile', ['user' => User::currentModel()]);
    }

    public function update(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->redirect('/login');
        }
        foreach (['name', 'phone', 'company', 'address', 'city', 'state', 'country', 'postal_code', 'locale', 'timezone', 'currency'] as $f) {
            $val = $request->input($f);
            if ($val !== null) {
                $user->$f = $val;
            }
        }
        $user->save();
        flash('success', __('account.profile_saved'));
        return $this->redirect('/account/profile');
    }

    public function security(Request $request): Response
    {
        return $this->view('account/security', ['user' => User::currentModel()]);
    }

    public function changePassword(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->redirect('/login');
        }
        $current = (string) $request->input('current_password');
        $new = (string) $request->input('new_password');
        if (!$user->checkPassword($current)) {
            flash('error', __('account.current_password_invalid'));
            return $this->back();
        }
        if (strlen($new) < 8) {
            flash('error', __('account.password_too_short'));
            return $this->back();
        }
        $user->setPassword($new);
        $user->save();
        flash('success', __('account.password_changed'));
        return $this->back();
    }
}
