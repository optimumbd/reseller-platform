<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Core\App;
use App\Core\Mailer;
use App\Models\EmailTemplate;
use App\Models\Notification;

final class NotificationService
{
    private Mailer $mailer;

    public function __construct()
    {
        $this->mailer = new Mailer((array) (App::getInstance()->config('mail') ?? []));
    }

    public function sendEmailVerification(array $user, string $token): void
    {
        $url = url('/email/verify/' . $token);
        $body = '<p>Hello ' . e($user['name']) . ',</p>'
            . '<p>Please verify your email address by clicking the link below:</p>'
            . '<p><a href="' . $url . '">' . $url . '</a></p>'
            . '<p>If you did not register, please ignore this email.</p>';
        $this->sendEmail((string) $user['email'], 'Verify your email address', $body);
    }

    public function sendPasswordReset(array $user, string $token): void
    {
        $url = url('/reset-password/' . $token);
        $body = '<p>Hello ' . e($user['name']) . ',</p>'
            . '<p>You requested a password reset. Click the link below to set a new password:</p>'
            . '<p><a href="' . $url . '">' . $url . '</a></p>'
            . '<p>If you did not request this, please ignore this email.</p>';
        $this->sendEmail((string) $user['email'], 'Password reset request', $body);
    }

    public function sendMagicLink(array $user, string $token): void
    {
        $url = url('/magic-link/verify/' . $token);
        $body = '<p>Hello ' . e($user['name']) . ',</p>'
            . '<p>Click the link below to sign in (valid for 15 minutes):</p>'
            . '<p><a href="' . $url . '">' . $url . '</a></p>';
        $this->sendEmail((string) $user['email'], 'Sign in to your account', $body);
    }

    public function sendInvoice(array $user, array $invoice): void
    {
        $body = '<p>Hello ' . e($user['name']) . ',</p>'
            . '<p>Your invoice <strong>' . e($invoice['number']) . '</strong> has been generated.</p>'
            . '<p>Total: ' . money($invoice['total'], $invoice['currency']) . '</p>'
            . '<p><a href="' . url('/account/invoices/' . $invoice['id']) . '">View invoice</a></p>';
        $this->sendEmail((string) $user['email'], 'New invoice ' . $invoice['number'], $body);
    }

    public function inApp(int $userId, string $type, string $title, string $body, ?string $url = null): void
    {
        $n = new Notification([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
        $n->save();
    }

    public function sendEmail(string $to, string $subject, string $html, ?string $text = null): bool
    {
        return $this->mailer->send($to, $subject, $html, $text);
    }

    public function fromTemplate(string $key, string $to, array $vars = []): bool
    {
        $tpl = EmailTemplate::findByKey($key);
        if (!$tpl) {
            return false;
        }
        $subject = (string) $tpl->subject;
        $body = (string) $tpl->body;
        foreach ($vars as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', (string) $v, $subject);
            $body = str_replace('{{' . $k . '}}', (string) $v, $body);
        }
        return $this->mailer->send($to, $subject, $body);
    }
}
