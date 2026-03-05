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
    $mail->Password   = 'nluqnnkminsfphlj';

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->Timeout    = 10; // 10 seconds timeout

    $mail->setFrom('calmayuan0@gmail.com', 'CK Resort');

    return $mail;
}