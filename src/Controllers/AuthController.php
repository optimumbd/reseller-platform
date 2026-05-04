<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Helpers\SecurityHelper;
use App\Models\EmailVerification;
use App\Models\MagicLink;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Notifications\NotificationService;

final class AuthController extends BaseController
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login');
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email');
        $password = (string) $request->input('password');
        $user = User::findByEmail($email);
        if (!$user || !$user->checkPassword($password)) {
            flash('error', __('auth.invalid_credentials'));
            return $this->back();
        }
        if (($user->status ?? 'active') !== 'active') {
            flash('error', __('auth.account_disabled'));
            return $this->back();
        }
        if (!empty($user->two_factor_enabled)) {
            $this->app()->session->put('2fa_pending_user', (int) $user->id);
            return $this->redirect('/two-factor/challenge');
        }
        $this->app()->session->login((int) $user->id);
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip;
        $user->save();
        $intended = $this->app()->session->getFlash('intended');
        return $this->redirect($intended ? '/' . ltrim($intended, '/') : ($user->isAdmin() ? '/admin' : '/account'));
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('auth/register');
    }

    public function register(Request $request): Response
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|min:2|max:120',
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|min:8',
            'terms' => 'required',
        ]);
        if ($v->fails()) {
            flash('errors', $v->errors());
            flash('old', $request->all());
            return $this->back();
        }
        $user = new User([
            'name' => $request->input('name'),
            'email' => strtolower((string) $request->input('email')),
            'role' => 'customer',
            'status' => 'active',
            'locale' => current_locale(),
            'currency' => current_currency(),
            'timezone' => config('app.timezone', 'UTC'),
        ]);
        $user->setPassword((string) $request->input('password'));
        $user->save();

        // Send email verification
        $token = bin2hex(random_bytes(32));
        $verification = new EmailVerification([
            'user_id' => (int) $user->id,
            'email' => $user->email,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 24 * 3600),
        ]);
        $verification->save();
        try {
            (new NotificationService())->sendEmailVerification((array) $user->attributes, $token);
        } catch (\Throwable) {
            // ignore mail errors during local dev
        }

        $this->app()->session->login((int) $user->id);
        flash('success', __('auth.registration_complete'));
        return $this->redirect('/account');
    }

    public function logout(Request $request): Response
    {
        $this->app()->session->logout();
        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('auth/forgot');
    }

    public function forgot(Request $request): Response
    {
        $email = strtolower((string) $request->input('email'));
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $reset = new PasswordReset([
                'email' => $email,
                'token' => SecurityHelper::tokenHash($token),
            ]);
            $reset->save();
            try {
                (new NotificationService())->sendPasswordReset((array) $user->attributes, $token);
            } catch (\Throwable) {
            }
        }
        flash('success', __('auth.password_reset_sent'));
        return $this->back();
    }

    public function showReset(Request $request, string $token): Response
    {
        return $this->view('auth/reset', ['token' => $token]);
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->input('token');
        $email = strtolower((string) $request->input('email'));
        $password = (string) $request->input('password');
        $row = PasswordReset::whereOne('email = :e AND token = :t ORDER BY id DESC', [
            'e' => $email,
            't' => SecurityHelper::tokenHash($token),
        ]);
        if (!$row || strtotime((string) $row->created_at) < time() - 3600) {
            flash('error', __('auth.invalid_token'));
            return $this->back();
        }
        $user = User::findByEmail($email);
        if (!$user) {
            flash('error', __('auth.invalid_token'));
            return $this->back();
        }
        $user->setPassword($password);
        $user->save();
        $row->delete();
        flash('success', __('auth.password_reset_complete'));
        return $this->redirect('/login');
    }

    public function verifyEmail(Request $request, string $token): Response
    {
        $row = EmailVerification::whereOne('token = :t', ['t' => $token]);
        if (!$row || strtotime((string) $row->expires_at) < time()) {
            flash('error', __('auth.invalid_or_expired_token'));
            return $this->redirect('/login');
        }
        $user = User::find((int) $row->user_id);
        if ($user) {
            $user->email_verified_at = now();
            $user->save();
        }
        $row->verified_at = now();
        $row->save();
        flash('success', __('auth.email_verified'));
        return $this->redirect('/account');
    }

    public function showMagic(Request $request): Response
    {
        return $this->view('auth/magic-link');
    }

    public function sendMagic(Request $request): Response
    {
        $email = strtolower((string) $request->input('email'));
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $link = new MagicLink([
                'user_id' => (int) $user->id,
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + 900),
            ]);
            $link->save();
            try {
                (new NotificationService())->sendMagicLink((array) $user->attributes, $token);
            } catch (\Throwable) {
            }
        }
        flash('success', __('auth.magic_link_sent'));
        return $this->back();
    }

    public function verifyMagic(Request $request, string $token): Response
    {
        $link = MagicLink::whereOne('token = :t AND used_at IS NULL', ['t' => $token]);
        if (!$link || strtotime((string) $link->expires_at) < time()) {
            flash('error', __('auth.invalid_or_expired_token'));
            return $this->redirect('/login');
        }
        $link->used_at = now();
        $link->save();
        $this->app()->session->login((int) $link->user_id);
        return $this->redirect('/account');
    }
}
