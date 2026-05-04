<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mailer with PHPMailer when available, otherwise falling back to native
 * PHP mail() so the platform runs on bare shared hosting.
 */
final class Mailer
{
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = []): bool
    {
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return $this->sendViaPHPMailer($to, $subject, $htmlBody, $textBody, $attachments);
        }
        return $this->sendViaNativeMail($to, $subject, $htmlBody, $textBody);
    }

    private function sendViaPHPMailer(string $to, string $subject, string $htmlBody, ?string $textBody, array $attachments): bool
    {
        try {
            /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
            $cls = '\\PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $cls(true);
            $driver = $this->config['driver'] ?? 'smtp';
            if ($driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host = $this->config['host'] ?? '';
                $mail->Port = (int) ($this->config['port'] ?? 587);
                $mail->SMTPAuth = !empty($this->config['user']);
                $mail->Username = $this->config['user'] ?? '';
                $mail->Password = $this->config['pass'] ?? '';
                $enc = strtolower((string) ($this->config['encryption'] ?? 'tls'));
                if ($enc === 'ssl') {
                    $mail->SMTPSecure = 'ssl';
                } elseif ($enc === 'tls') {
                    $mail->SMTPSecure = 'tls';
                }
            } else {
                $mail->isMail();
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(
                (string) ($this->config['from_address'] ?? 'no-reply@example.com'),
                (string) ($this->config['from_name'] ?? 'Reseller Platform')
            );
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            if ($textBody !== null) {
                $mail->AltBody = $textBody;
            }
            foreach ($attachments as $att) {
                if (is_string($att) && is_file($att)) {
                    $mail->addAttachment($att);
                } elseif (is_array($att) && isset($att['path'])) {
                    $mail->addAttachment($att['path'], $att['name'] ?? '');
                }
            }
            return (bool) $mail->send();
        } catch (\Throwable $e) {
            App::getInstance()->logger->error('Mail send failed: ' . $e->getMessage(), ['to' => $to, 'subject' => $subject]);
            return false;
        }
    }

    private function sendViaNativeMail(string $to, string $subject, string $htmlBody, ?string $textBody): bool
    {
        $from = (string) ($this->config['from_address'] ?? 'no-reply@example.com');
        $fromName = (string) ($this->config['from_name'] ?? 'Reseller Platform');
        $boundary = bin2hex(random_bytes(8));

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . $fromName . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: ResellerPlatform',
        ];

        $plain = $textBody ?? trim(strip_tags($htmlBody));
        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $plain . "\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $htmlBody . "\r\n\r\n"
            . "--{$boundary}--";

        try {
            return @mail($to, $subject, $body, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            App::getInstance()->logger->error('Native mail send failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }
}
