<?php
require_once 'api_init.php';
require_once __DIR__ . '/../../includes/email-smtp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST method required'], 405);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$name = sanitize($data['name'] ?? '');
$email = sanitize($data['email'] ?? '');
$subject = sanitize($data['subject'] ?? '');
$message = sanitize($data['comment'] ?? $data['message'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($message)) {
    json_response(['error' => 'Name, Email and Message are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email format'], 400);
}

// Get admin email
$admin_email = get_setting($pdo, 'store_email', 'goappalam@gmail.com');

// Format email body
$email_subject = "New Customer Query: " . ($subject ?: "General Inquiry");
$email_body = "
    <h2>New Contact Form Submission</h2>
    <p><strong>Name:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Subject:</strong> " . ($subject ?: "N/A") . "</p>
    <p><strong>Message:</strong></p>
    <p>" . nl2br($message) . "</p>
    <hr>
    <p>This message was sent from the Goappalam website contact form.</p>
";

try {
    // Send email to admin
    $sent = sendEmail($admin_email, $email_subject, $email_body);
    
    // Also store in database if table exists
    try {
        $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'contact_messages'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
            $stmt->execute([$name, $email, $subject, $message]);
        }
    } catch (Exception $e) {
        // Silent fail for DB storage if table doesn't exist
    }

    if ($sent) {
        log_api_call($pdo, 200);
        json_response(['status' => 'success', 'message' => 'Your message has been sent successfully.']);
    } else {
        log_api_call($pdo, 500, 'Failed to send email');
        json_response(['error' => 'Failed to send your message. Please try again later.'], 500);
    }
} catch (Exception $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Server error: ' . $e->getMessage()], 500);
}
