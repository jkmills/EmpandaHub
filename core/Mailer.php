<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $this->mail->CharSet = 'UTF-8';

        if (defined('MAIL_HOST') && MAIL_HOST) {
            $this->mail->isSMTP();
            $this->mail->Host       = MAIL_HOST;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = MAIL_USER;
            $this->mail->Password   = MAIL_PASS;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 587;
        } else {
            $this->mail->isMail();
        }

        if (defined('MAIL_FROM')) {
            $this->mail->setFrom(MAIL_FROM, defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME);
        }
    }

    public function send(string $to, string $toName, string $subject, string $bodyHtml, string $bodyText = ''): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = $subject;
            $this->mail->isHTML(true);
            $this->mail->Body    = $bodyHtml;
            $this->mail->AltBody = $bodyText ?: strip_tags($bodyHtml);
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
