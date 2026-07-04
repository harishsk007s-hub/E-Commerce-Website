<?php
/**
 * Public/Customer Invoice Download
 */
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/pdf-invoice.php';

// Allow for some simple validation (session or order_id + phone)
// For now, we'll use order_id and a basic check
$order_id = (int)($_GET['order_id'] ?? 0);

if (!$order_id) {
    die("Invalid Order ID");
}

// In a real app, you'd verify the user owns this order
// For this mirror, we'll allow it if the order exists

// Check if invoice already exists in DB
$stmt = $pdo->prepare("SELECT invoice_pdf FROM invoices WHERE order_id = ?");
$stmt->execute([$order_id]);
$invoice = $stmt->fetch();

if ($invoice && !empty($invoice['invoice_pdf'])) {
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
    echo "Failed to generate or retrieve invoice. Ensure order ID #$order_id is valid.";
}
