<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $amount = $_POST['amount'];

    try {

        $mail = getMailer();

        // receiver (guest)
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Payment Receipt";

        $mail->Body = "
        <h2>Payment Receipt</h2>
        <p>Thank you for your payment.</p>
        <p><strong>Amount Paid:</strong> ₱$amount</p>
        <p>This serves as your official receipt.</p>
        ";

        $mail->send();

        echo "Receipt sent successfully!";

    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
}