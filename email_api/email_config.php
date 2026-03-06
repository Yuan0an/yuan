<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

if (!function_exists('getMailer')) {
    function getMailer() {

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'calmayuan0@gmail.com';   // admin gmail
        $mail->Password   = 'lpxi ugef jwjh fcqk';     // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('calmayuan0@gmail.com', 'CK Resort');

        return $mail;
    }
}