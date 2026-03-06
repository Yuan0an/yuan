<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function getMailer($echo_debug = false) {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    
    // Use IPv4 specifically - fixes many cloud connection issues
    $mail->Host       = gethostbyname('smtp.gmail.com'); 
    $mail->SMTPAuth   = true;

    // COMPANY EMAIL
    $mail->Username   = 'calmayuan0@gmail.com';
    $mail->Password   = 'nluqnnkminsfphlj';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;

    $mail->Timeout    = 30; // Increased timeout
    
    // Log debug info to server's error log or echo it for the test tool
    $mail->SMTPDebug  = 2; 
    if ($echo_debug) {
        $mail->Debugoutput = 'html';
    } else {
        $mail->Debugoutput = function($str, $level) {
            error_log("[SMTP DEBUG] $str");
        };
    }

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