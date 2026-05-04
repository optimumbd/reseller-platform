<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\Security\TotpService;

final class TwoFactorController extends BaseController
{
    public function challenge(Request $request): Response
    {
        if (!$this->app()->session->get('2fa_pending_user')) {
            return $this->redirect('/login');
        }
        return $this->view('auth/2fa-challenge');
    }

    public function verify(Request $request): Response
    {
        $userId = (int) ($this->app()->session->get('2fa_pending_user') ?? 0);
        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            return $this->redirect('/login');
        }
        $code = (string) $request->input('code');
        if (!TotpService::verify((string) $user->two_factor_secret, $code)) {
            flash('error', __('auth.invalid_code'));
            return $this->back();
        }
        $this->app()->session->forget('2fa_pending_user');
        $this->app()->session->login((int) $user->id);
        return $this->redirect($user->isAdmin() ? '/admin' : '/account');
    }

    public function setup(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->redirect('/login');
        }
        $secret = TotpService::generateSecret();
        $this->app()->session->put('2fa_setup_secret', $secret);
        $issuer = (string) config('app.name', 'Reseller');
        $uri = TotpService::otpauthUri($issuer, (string) $user->email, $secret);
        return $this->view('account/2fa-setup', [
            'secret' => $secret,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($uri),
            'uri' => $uri,
        ]);
    }

    public function enable(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->redirect('/login');
        }
        $secret = (string) $this->app()->session->get('2fa_setup_secret');
        $code = (string) $request->input('code');
        if (!TotpService::verify($secret, $code)) {
            flash('error', __('auth.invalid_code'));
            return $this->back();
        }
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = 1;
        $user->save();
        $this->app()->session->forget('2fa_setup_secret');
        flash('success', __('auth.2fa_enabled'));
        return $this->redirect('/account/security');
    }

    public function disable(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->redirect('/login');
        }
        $user->two_factor_enabled = 0;
        $user->two_factor_secret = null;
        $user->save();
        flash('success', __('auth.2fa_disabled'));
        return $this->redirect('/account/security');
    }
}
