<?php
require_once __DIR__ . '/api/v1/api_init.php';
require_once __DIR__ . '/includes/email-smtp.php';

$email = 'sales@goappalam.in';
$subject = 'Test Email';
$body = 'This is a test email from Goappalam.';

try {
    echo "Attempting to send email to $email...\n";
    if (sendEmail($email, $subject, $body)) {
        echo "Email sent successfully!\n";
    } else {
        echo "Failed to send email (sendEmail returned false).\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
