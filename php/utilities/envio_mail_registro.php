<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config/config.php';

function enviarMail($destino, $nombre,$apellido, $usuario) {
    global $smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_secure;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port = $smtp_port;
$mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];
        $mail->setFrom($smtp_user, 'Sistema PLEI [E.E.S.T N 1]');
        $mail->addAddress($destino, $nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Cuenta creada en sistema PLEI';
        $mail->Body    = "Hola $nombre $apellido<br>Tu usuario es: <b>$usuario</b><br>Y tu contraseña es: <b>tu número de documento</b>
        <br><br>Saluda atentamente equipo de conducción.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Error al enviar correo: {$mail->ErrorInfo}";
    }
}
?>