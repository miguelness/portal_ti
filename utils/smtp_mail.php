<?php
// utils/smtp_mail.php — envio SMTP com PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail(string $toEmail, string $toName, string $verifyLink): bool {
    $config = require __DIR__ . '/../smtp_config.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($config['from']['address'], $config['from']['name']);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->Subject = 'Verifique seu e-mail • Portal Grupo Barão';

        $bodyText = "Olá $toName,\n\n" .
            "Recebemos seu cadastro no Portal. Clique no link abaixo para confirmar seu e-mail:\n\n" .
            "$verifyLink\n\n" .
            "Se você não solicitou, ignore esta mensagem.";

        $bodyHtml = '<p>Olá ' . htmlspecialchars($toName) . ',</p>' .
            '<p>Recebemos seu cadastro no Portal. Clique no botão abaixo para confirmar seu e-mail:</p>' .
            '<p><a href="' . htmlspecialchars($verifyLink) . '" style="display:inline-block;padding:10px 16px;background:#206bc4;color:#fff;border-radius:6px;text-decoration:none">Confirmar e-mail</a></p>' .
            '<p>Se você não solicitou, ignore esta mensagem.</p>';

        $mail->isHTML(true);
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('SMTP error: ' . $e->getMessage());
        return false;
    }
}

?>
