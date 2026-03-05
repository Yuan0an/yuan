<?php
require_once __DIR__ . '/config.php';

/**
 * sendBookingConfirmationEmail()
 *
 * Sends a Booking Reservation Confirmation email to the guest
 * after a successful reservation is saved to the database.
 *
 * @param int    $booking_id     The auto-generated booking ID (reference number)
 * @param string $guest_email    Guest's email address
 * @param string $guest_name     Guest's full name
 * @param string $event_title    The event/function name entered by the guest
 * @param string $event_type     e.g. Birthday, Wedding, Corporate
 * @param string $tour_type      e.g. Day Tour, Night Tour, Overnight Stay
 * @param int    $guests         Number of expected guests
 * @param string $booking_date   Reservation date (YYYY-MM-DD)
 * @param string $start_time     Session start time (HH:MM:SS)
 * @param string $end_time       Session end time (HH:MM:SS)
 * @param bool   $is_overnight   Whether the event spans overnight
 *
 * @return bool  true on success, false on failure
 */
function sendBookingConfirmationEmail(
    $booking_id,
    $guest_email,
    $guest_name,
    $event_title,
    $event_type,
    $tour_type,
    $guests,
    $booking_date,
    $start_time,
    $end_time,
    $is_overnight = false
) {
    try {
        $mail = getMailer();

        // Recipient
        $mail->addAddress($guest_email, $guest_name);

        // Format dates for display
        $checkin_display  = date('F j, Y', strtotime($booking_date))
                          . ' at ' . date('h:i A', strtotime($start_time));

        // For overnight bookings the checkout date is the next day
        if ($is_overnight) {
            $checkout_date = date('Y-m-d', strtotime($booking_date . ' +1 day'));
        } else {
            $checkout_date = $booking_date;
        }
        $checkout_display = date('F j, Y', strtotime($checkout_date))
                          . ' at ' . date('h:i A', strtotime($end_time));

        $booking_date_display = date('F j, Y', strtotime($booking_date));
        $today_display        = date('F j, Y');

        // Padded reference number, e.g. CK-000042
        $ref_number = 'CK-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);

        // ----------------------------------------------------------------
        // Email content
        // ----------------------------------------------------------------
        $mail->isHTML(true);
        $mail->Subject = 'Booking Reservation Confirmation';
        $mail->Body    = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Confirmation</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0"
               style="background:#ffffff;border-radius:10px;overflow:hidden;
                      box-shadow:0 4px 20px rgba(0,0,0,0.08);">

          <!-- ── HEADER ── -->
          <tr>
            <td align="center"
                style="background:linear-gradient(135deg,#2e7d32,#4CAF50);
                       padding:40px 30px;">
              <div style="font-size:48px;margin-bottom:10px;">🎉</div>
              <h1 style="margin:0;color:#ffffff;font-size:28px;letter-spacing:1px;">
                Booking Reservation
              </h1>
              <p style="margin:10px 0 0;color:#c8e6c9;font-size:16px;">
                Thank you for booking with us!
              </p>
            </td>
          </tr>

          <!-- ── REFERENCE BANNER ── -->
          <tr>
            <td align="center"
                style="background:#e8f5e9;padding:18px 30px;
                       border-bottom:2px dashed #a5d6a7;">
              <p style="margin:0;color:#555;font-size:13px;
                         text-transform:uppercase;letter-spacing:1px;">
                Booking Reference No.
              </p>
              <p style="margin:6px 0 0;color:#2e7d32;font-size:28px;
                         font-weight:bold;letter-spacing:3px;">
                ' . htmlspecialchars($ref_number) . '
              </p>
            </td>
          </tr>

          <!-- ── BODY ── -->
          <tr>
            <td style="padding:30px 40px;">

              <p style="margin:0 0 25px;color:#555;font-size:15px;line-height:1.6;">
                Dear <strong>' . htmlspecialchars($guest_name) . '</strong>,<br>
                Your reservation request has been received and is currently
                <strong>pending review</strong> by our team.
                You will be notified once it is confirmed.
              </p>

              <!-- Event Title -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:#f9fbe7;border-left:4px solid #8bc34a;
                            border-radius:4px;margin-bottom:25px;">
                <tr>
                  <td style="padding:14px 18px;">
                    <p style="margin:0;color:#888;font-size:11px;
                               text-transform:uppercase;letter-spacing:1px;">
                      Event Title
                    </p>
                    <p style="margin:4px 0 0;color:#33691e;font-size:20px;
                               font-weight:bold;">
                      ' . htmlspecialchars($event_title) . '
                    </p>
                  </td>
                </tr>
              </table>

              <!-- ── Booking Details ── -->
              <h3 style="margin:0 0 14px;color:#2e7d32;font-size:15px;
                          text-transform:uppercase;letter-spacing:1px;
                          border-bottom:1px solid #e0e0e0;padding-bottom:8px;">
                📋 Booking Details
              </h3>
              <table width="100%" cellpadding="8" cellspacing="0"
                     style="font-size:14px;color:#444;margin-bottom:25px;">
                <tr style="background:#f9f9f9;">
                  <td width="45%" style="padding:10px 12px;color:#888;
                               font-size:12px;text-transform:uppercase;">
                    Event Name
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($event_title) . '
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 12px;color:#888;
                              font-size:12px;text-transform:uppercase;">
                    Event Type
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($event_type) . '
                  </td>
                </tr>
                <tr style="background:#f9f9f9;">
                  <td style="padding:10px 12px;color:#888;
                              font-size:12px;text-transform:uppercase;">
                    Tour Type
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($tour_type) . '
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 12px;color:#888;
                              font-size:12px;text-transform:uppercase;">
                    Expected Guests
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . intval($guests) . ' pax
                  </td>
                </tr>
              </table>

              <!-- ── Reservation Information ── -->
              <h3 style="margin:0 0 14px;color:#2e7d32;font-size:15px;
                          text-transform:uppercase;letter-spacing:1px;
                          border-bottom:1px solid #e0e0e0;padding-bottom:8px;">
                📅 Reservation Information
              </h3>
              <table width="100%" cellpadding="8" cellspacing="0"
                     style="font-size:14px;color:#444;margin-bottom:30px;">
                <tr style="background:#f9f9f9;">
                  <td width="45%" style="padding:10px 12px;color:#888;
                               font-size:12px;text-transform:uppercase;">
                    Check-in Date
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($checkin_display) . '
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 12px;color:#888;
                              font-size:12px;text-transform:uppercase;">
                    Check-out Date
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($checkout_display) . '
                  </td>
                </tr>
                <tr style="background:#f9f9f9;">
                  <td style="padding:10px 12px;color:#888;
                              font-size:12px;text-transform:uppercase;">
                    Booking Date
                  </td>
                  <td style="padding:10px 12px;font-weight:600;">
                    ' . htmlspecialchars($today_display) . '
                  </td>
                </tr>
              </table>

              <!-- ── Closing message ── -->
              <p style="margin:0;color:#555;font-size:14px;line-height:1.8;
                         text-align:center;padding:20px;background:#f1f8e9;
                         border-radius:8px;">
                Thank you for choosing our resort.<br>
                <strong style="color:#2e7d32;">
                  We look forward to hosting your event! 🌴
                </strong>
              </p>

            </td>
          </tr>

          <!-- ── FOOTER ── -->
          <tr>
            <td align="center"
                style="background:#2e7d32;padding:20px 30px;">
              <p style="margin:0;color:#a5d6a7;font-size:12px;">
                This is an automated confirmation. Please do not reply to this email.
              </p>
              <p style="margin:6px 0 0;color:#81c784;font-size:12px;">
                © ' . date('Y') . ' CK Resort. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>';

        // Plain-text fallback
        $mail->AltBody =
            "Booking Reservation\n" .
            "====================\n\n" .
            "Thank you for booking with us!\n\n" .
            "Booking Reference No: {$ref_number}\n\n" .
            "Event Title: {$event_title}\n\n" .
            "Booking Details:\n" .
            "  Event Name    : {$event_title}\n" .
            "  Event Type    : {$event_type}\n" .
            "  Tour Type     : {$tour_type}\n" .
            "  Expected Guests: {$guests} pax\n\n" .
            "Reservation Information:\n" .
            "  Check-in Date : {$checkin_display}\n" .
            "  Check-out Date: {$checkout_display}\n" .
            "  Booking Date  : {$today_display}\n\n" .
            "Thank you for choosing our resort.\n" .
            "We look forward to hosting your event!\n";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log silently — booking is NOT affected by email failure
        error_log('[CK Resort Email] Failed to send booking confirmation to '
                  . $guest_email . ': ' . $e->getMessage());
        return false;
    }
}
