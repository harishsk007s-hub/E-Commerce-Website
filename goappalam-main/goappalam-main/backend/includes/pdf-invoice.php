<?php
/**
 * PDF Invoice Generator using TCPDF
 * Requires TCPDF library (use: composer require tecnickcom/tcpdf)
 */

// Include TCPDF if composer autoload is present
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/db_connection.php';

/**
 * Generate PDF Invoice and save/return it
 */
function generateInvoicePDF($orderId, $saveToDB = true) {
    global $pdo;

    // Fetch order details
    $stmt = $pdo->prepare("SELECT o.*, c.name as customer_name, c.email as customer_email FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) return null;

    // Store settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'general' OR setting_group = 'tax'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $items = json_decode($order['items'], true) ?: [];

    // Create TCPDF document if it's available
    if (!class_exists('TCPDF')) {
        // Fallback or error if TCPDF is not installed
        error_log("TCPDF class not found. Please install it.");
        return null;
    }

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($settings['store_name'] ?? 'Goappalam');
    $pdf->SetTitle('Invoice #' . $orderId);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // HTML Content
    $html = '
    <table width="100%" cellpadding="5">
        <tr>
            <td width="50%"><h1>INVOICE</h1></td>
            <td width="50%" align="right">
                <b>' . ($settings['store_name'] ?? 'Goappalam') . '</b><br>
                ' . ($settings['store_email'] ?? 'admin@goappalam.in') . '<br>
                GSTIN: ' . ($settings['store_gstin'] ?? '33AAAAA0000A1Z5') . '
            </td>
        </tr>
    </table>
    <hr>
    <table width="100%" cellpadding="5">
        <tr>
            <td width="50%">
                <b>Bill To:</b><br>
                ' . ($order['shipping_name'] ?? $order['customer_name']) . '<br>
                ' . ($order['shipping_address1'] ?? '') . '<br>
                ' . ($order['shipping_address2'] ?? '') . '<br>
                ' . ($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' - ' . ($order['shipping_pincode'] ?? '') . '<br>
                Phone: ' . ($order['shipping_phone'] ?? '') . '
            </td>
            <td width="50%" align="right">
                Order ID: #' . $orderId . '<br>
                Date: ' . date('d-m-Y', strtotime($order['created_at'])) . '<br>
                Payment: ' . strtoupper($order['payment_method']) . '<br>
                Status: ' . strtoupper($order['payment_status']) . '
                ' . ($order['payment_method'] == 'cod' ? '<br>Delivery OTP: ' . $order['cod_delivery_otp'] : '') . '
            </td>
        </tr>
    </table>
    <br><br>
    <table border="1" cellpadding="5" width="100%">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="50%">Item</th>
                <th width="15%">Qty</th>
                <th width="15%">Price</th>
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($items as $item) {
        $html .= '<tr>
            <td>' . ($item['name'] ?? 'Product') . '</td>
            <td align="center">' . ($item['qty'] ?? 1) . '</td>
            <td align="right">₹' . number_format($item['price'] ?? 0, 2) . '</td>
            <td align="right">₹' . number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) . '</td>
        </tr>';
    }

    $html .= '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right"><b>Subtotal:</b></td>
                <td align="right">₹' . number_format($order['subtotal'], 2) . '</td>
            </tr>
            <tr>
                <td colspan="3" align="right"><b>Tax (' . ($settings['tax_gst_percentage'] ?? '5') . '%):</b></td>
                <td align="right">₹' . number_format($order['tax_amount'], 2) . '</td>
            </tr>
            <tr>
                <td colspan="3" align="right"><b>Shipping:</b></td>
                <td align="right">₹' . number_format($order['shipping_fee'], 2) . '</td>
            </tr>
            ' . ($order['discount_amount'] > 0 ? '<tr><td colspan="3" align="right"><b>Discount:</b></td><td align="right">-₹' . number_format($order['discount_amount'], 2) . '</td></tr>' : '') . '
            <tr style="background-color: #f2f2f2;">
                <td colspan="3" align="right"><b>Grand Total:</b></td>
                <td align="right"><b>₹' . number_format($order['total'], 2) . '</b></td>
            </tr>
        </tfoot>
    </table>';

    $pdf->writeHTML($html);
    $pdfData = $pdf->Output('invoice_' . $orderId . '.pdf', 'S');

    if ($saveToDB) {
        $stmt = $pdo->prepare("INSERT INTO invoices (order_id, invoice_pdf) VALUES (?, ?) ON DUPLICATE KEY UPDATE invoice_pdf = ?, created_at = NOW()");
        $stmt->execute([$orderId, $pdfData, $pdfData]);
    }

    return $pdfData;
}
