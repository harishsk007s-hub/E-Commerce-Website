<?php
/**
 * PHPMailer Configuration & Email Sending
 * Includes a pure PHP SMTP fallback for environments without libraries.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

/**
 * Send an email using SMTP (PHPMailer) or a direct PHP Socket implementation
 */
function sendEmail($to, $subject, $body, $attachment = null) {
    // Load config from .env
    $smtp_host = getenv('SMTP_HOST') ?: 'mail.goappalam.in';
    $smtp_user = getenv('SMTP_USER') ?: '';
    $smtp_pass = getenv('SMTP_PASS') ?: '';
    $smtp_port = getenv('SMTP_PORT') ?: 465;
    $smtp_secure = getenv('SMTP_SECURE') ?: 'ssl';
    $from_name = getenv('EMAIL_FROM_NAME') ?: 'Goappalam';
    $from_address = getenv('EMAIL_FROM_ADDRESS') ?: $smtp_user;

    // 1. Try Direct PHP SMTP (Bypasses all library requirements)
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return smtp_direct_send($to, $subject, $body, $smtp_host, $smtp_user, $smtp_pass, $smtp_port);
    }
    
    // 2. Use PHPMailer if available
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug  = 0; 
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = $smtp_secure; 
        $mail->Port       = $smtp_port;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 20; 

        $mail->setFrom($from_address, $from_name);
        $mail->addAddress($to);

        if ($attachment && isset($attachment['data']) && isset($attachment['name'])) {
            $mail->addStringAttachment($attachment['data'], $attachment['name']);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer failed: " . $e->getMessage());
        return smtp_direct_send($to, $subject, $body, $smtp_host, $smtp_user, $smtp_pass, $smtp_port);
    }
}

/**
 * PURE PHP SMTP SENDER (No Library Required)
 * Connects directly to SMTP using fsockopen
 */
function smtp_direct_send($to, $subject, $body, $host = null, $user = null, $pass = null, $port = null) {
    $user = $user ?: (getenv('SMTP_USER') ?: '');
    $pass = $pass ?: (getenv('SMTP_PASS') ?: '');
    $host_raw = $host ?: (getenv('SMTP_HOST') ?: 'mail.goappalam.in');
    $port = $port ?: (getenv('SMTP_PORT') ?: 465);
    $from_name = getenv('EMAIL_FROM_NAME') ?: 'Goappalam';
    $from_address = getenv('EMAIL_FROM_ADDRESS') ?: $user;
    $timeout = 15;

    // Ensure host has prefix for fsockopen if using SSL (Port 465)
    $is_ssl = ($port == 465);
    $is_tls = ($port == 587);
    $host_conn = ($is_ssl && strpos($host_raw, '://') === false) ? 'ssl://' . $host_raw : $host_raw;

    $socket = @fsockopen($host_conn, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("SMTP Socket Error: $errstr ($errno)");
        // Last resort: native mail()
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: $from_name <$from_address>";
        return @mail($to, $subject, $body, $headers);
    }

    $get_response = function($socket) {
        $res = "";
        while ($str = fgets($socket, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        error_log("SMTP Response: " . trim($res));
        return $res;
    };

    try {
        error_log("SMTP: Connecting to $host_conn on $port");
        $get_response($socket);
        
        error_log("SMTP: Sending EHLO");
        fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
        $get_response($socket);

        if ($is_tls) {
            error_log("SMTP: Sending STARTTLS");
            fwrite($socket, "STARTTLS\r\n");
            $get_response($socket);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to start TLS encryption");
            }
            error_log("SMTP: TLS started, sending EHLO again");
            fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
            $get_response($socket);
        }
        
        error_log("SMTP: Sending AUTH LOGIN");
        fwrite($socket, "AUTH LOGIN\r\n");
        $get_response($socket);
        
        fwrite($socket, base64_encode($user) . "\r\n");
        $get_response($socket);
        
        fwrite($socket, base64_encode($pass) . "\r\n");
        $get_response($socket);
        
        error_log("SMTP: Sending MAIL FROM");
        fwrite($socket, "MAIL FROM: <$from_address>\r\n");
        $get_response($socket);
        
        error_log("SMTP: Sending RCPT TO");
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $get_response($socket);
        
        error_log("SMTP: Sending DATA");
        fwrite($socket, "DATA\r\n");
        $get_response($socket);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "From: $from_name <$from_address>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        
        fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $get_response($socket);
        
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        @fclose($socket);
        error_log("SMTP Direct Error: " . $e->getMessage());
        return false;
    }
}
