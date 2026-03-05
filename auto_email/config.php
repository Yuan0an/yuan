<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function getMailer() {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    // COMPANY EMAIL
    $mail->Username   = 'calmayuan0@gmail.com';

    // APP PASSWORD (NOT normal password)
    // Ensure 2FA is enabled in your Google Account for this to work.
    $mail->Password   = 'nluqnnkminsfphlj';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL/Encrypted
    $mail->Port       = 465;

    $mail->Timeout    = 20; // 20 seconds timeout
    
    // Ignore SSL certificate issues (fixes many "connection failed" errors)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('calmayuan0@gmail.com', 'CK Resort');

    return $mail;
}