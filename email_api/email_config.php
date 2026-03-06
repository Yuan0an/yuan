<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function getMailer() {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'yourgmail@gmail.com';   // admin gmail
    $mail->Password   = 'your_app_password';     // Gmail App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('yourgmail@gmail.com', 'Resort Booking');

    return $mail;
}