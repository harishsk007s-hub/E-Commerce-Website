<?php
/**
 * Resend OTP logic
 */

session_start();
require_once __DIR__ . '/../includes/otp-system.php';
require_once __DIR__ . '/../includes/email-smtp.php';

if (!isset($_SESSION['login_email'])) {
    header('Location: otp-login.php');
    exit;
}

$email = $_SESSION['login_email'];

// Generate and Save OTP
$otp = generateOTP();
if (saveOTP($email, $otp, 'customer')) {
    // Send OTP via Email
    $subject = "Your Login OTP (Resent) - Goappalam";
    $body = "<h2>Hello,</h2><p>Your 6-digit OTP for login is: <b>$otp</b></p><p>This code expires in 10 minutes.</p>";
    
    if (sendEmail($email, $subject, $body)) {
        header('Location: otp-verify.php?resent=1');
        exit;
    } else {
        header('Location: otp-verify.php?error=send_failed');
        exit;
    }
} else {
    header('Location: otp-verify.php?error=save_failed');
    exit;
}
