<?php
/**
 * Invoice Download Handler
 */

require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/pdf-invoice.php';

check_admin_auth();
require_role(['super-admin', 'manager', 'orders']);

$order_id = (int)($_GET['order_id'] ?? 0);

if (!$order_id) {
    die("Invalid Order ID");
}

// Check if invoice already exists in DB
$stmt = $pdo->prepare("SELECT invoice_pdf FROM invoices WHERE order_id = ?");
$stmt->execute([$order_id]);
$invoice = $stmt->fetch();

if ($invoice) {
    $pdfData = $invoice['invoice_pdf'];
} else {
    // Generate new one
    $pdfData = generateInvoicePDF($order_id);
}

if ($pdfData) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Invoice_' . $order_id . '.pdf"');
    echo $pdfData;
} else {
    echo "Failed to generate or retrieve invoice.";
}
