<?php
require_once __DIR__ . '/bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $smtpHost = $_ENV['SMTP_HOST'] ?? '';
    $smtpUser = $_ENV['SMTP_USER'] ?? '';
    $smtpPass = $_ENV['SMTP_PASS'] ?? '';
    $smtpPort = (int)($_ENV['SMTP_PORT'] ?? 587);
    $smtpFrom = $_ENV['SMTP_FROM'] ?? '';
    $smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? 'Civic Connect';

    if (empty($smtpHost) || empty($smtpUser) || empty($smtpPass) || empty($smtpFrom)) {
        throw new Exception('SMTP configuration is incomplete. Check SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_FROM.');
    }

    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpPort;

    $mail->setFrom($smtpFrom, $smtpFromName);
    $mail->isHTML(true);

    return $mail;
}
