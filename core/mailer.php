<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

/**
 * PHPMailer ke zariye SMTP par HTML email bhejta hai.
 *
 * @param array<int, array{path:string,name?:string}> $attachments
 * @return array{0:bool,1:?string}
 */
function sendSmtpMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    ?string $replyToName = null,
    array $attachments = []
): array {
    // SMTP start karne se pehle recipient email ko saaf aur validate karo.
    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid recipient email.'];
    }

    // Zaroori SMTP settings .env/environment se aani chahiye.
    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        return [false, 'SMTP is not configured. Set SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD in environment.'];
    }

    // Sender email bhi valid honi chahiye.
    if (!filter_var(FROM_EMAIL, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid FROM_EMAIL configuration.'];
    }

    // PHPMailer class load karne ke liye composer autoload zaroori hai.
    if (!class_exists(PHPMailer::class)) {
        return [false, 'PHPMailer is not installed. Run: composer require phpmailer/phpmailer'];
    }

    try {
        // SMTP mail object banao aur basic config set karo.
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->CharSet = 'UTF-8';

        // Env config ke mutabiq TLS (587) ya SSL (465) use karo.
        if (SMTP_SECURE === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Main sender aur recipient set karo.
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Agar enable ho to admin ko monitoring ke liye BCC bhejo.
        if (EMAIL_BCC_ADMIN && filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
            $mail->addBCC(ADMIN_EMAIL);
        }

        // Reply-to diya ho to user ka reply support/admin inbox me aaye.
        if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
        }

        // Sirf wohi attachment lagao jo disk par waqai mojood ho.
        foreach ($attachments as $attachment) {
            $path = $attachment['path'] ?? '';
            if ($path !== '' && is_file($path)) {
                $mail->addAttachment($path, $attachment['name'] ?? basename($path));
            }
        }

        // Final email payload set karo.
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        // Crash ki bajaye error return karo taake logging/UI me show ho sake.
        return [false, $e->getMessage()];
    }
}
