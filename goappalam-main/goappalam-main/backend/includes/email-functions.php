<?php

require_once __DIR__ . '/email-smtp.php';

function send_verification_email($email, $token, $customer_name = 'Customer') {
    $subject = "Welcome to Goappalam – Complete Your Signup";
    
    $signup_link = FRONTEND_URL . "/login?token=" . $token;
    
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Hello {$customer_name},</h2>
        <p>Welcome to <strong>Goappalam</strong> 👋</p>
        <p>Thank you for signing up with us. To get started, please confirm your account by clicking the button below.</p>
        
        <div style='text-align: center; margin: 35px 0;'>
            <a href='{$signup_link}' style='background-color: #FFC222; color: #1E1D23; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block;'>Complete Your Signup</a>
        </div>
        
        <p style='font-size: 13px; color: #666;'>This is a secure link and will expire in 15 minutes.</p>
        
        <p>If the button above does not work, copy and paste the link below into your browser:</p>
        <p style='word-break: break-all; color: #FFC222;'>{$signup_link}</p>
        
        <p>Once your account is activated, you can log in and start exploring products, placing orders, and managing your account.</p>
        
        <p>If you did not create this account, please ignore this email.</p>
        
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    return sendEmail($email, $subject, $body);
}

function send_password_reset_email($email, $token) {
    $subject = "Reset Your Goappalam Password";
    
    $reset_link = "https://goappalam.in/reset-password?token=" . $token;
    
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Hello,</h2>
        <p>We received a request to reset your password for your Goappalam account. Click the button below to set a new password:</p>
        
        <div style='text-align: center; margin: 35px 0;'>
            <a href='{$reset_link}' style='background-color: #FFC222; color: #1E1D23; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block;'>Reset Password</a>
        </div>
        
        <p style='font-size: 13px; color: #666;'>This link will expire in 60 minutes. If you did not request a password reset, please ignore this email.</p>
        
        <p>If the button above does not work, please copy and paste the following link into your browser:</p>
        <p style='word-break: break-all; color: #FFC222;'>{$reset_link}</p>
        
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    return sendEmail($email, $subject, $body);
}

function send_order_status_email($email, $name, $order_id, $status) {
    $status_upper = strtoupper($status);
    $subject = "Order #$order_id Status Update: $status_upper";
    
    $status_messages = [
        'shipped' => "Great news! Your order #$order_id has been shipped and is on its way to you. You'll be enjoying your Goappalam products very soon!",
        'completed' => "Your order #$order_id has been successfully delivered. We hope you love your purchase! Thank you for shopping with us.",
        'cancelled' => "Your order #$order_id has been cancelled as per your request or due to unforeseen circumstances. If you have any questions, please reach out.",
        'refunded' => "Your order #$order_id has been refunded. The payment has been processed and should appear in your account within a few business days."
    ];
    
    $message = $status_messages[strtolower($status)] ?? "Your order #$order_id status has been updated to $status.";
    
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Hello $name,</h2>
        <p><strong>Order #$order_id Status Update</strong></p>
        <p>$message</p>
        
        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FFC222;'>
            <p style='margin: 0;'><strong>Current Status:</strong> <span style='color: #1E1D23; font-weight: bold;'>$status_upper</span></p>
        </div>
        
        <p>You can track your order history and details by logging into your account on our website.</p>
        <p>If you have any questions regarding your order, please contact our support team.</p>
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    return sendEmail($email, $subject, $body);
}

function send_cod_confirmation_email($email, $name, $order_id, $otp, $pdfData = null, $subtotal = 0, $shipping = 0, $tax = 0, $discount = 0, $total = 0, $items = [], $shipping_details = []) {
    $subject = "Your Goappalam Order is Confirmed";
    
    $items_html = "";
    if (is_array($items)) {
        foreach ($items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $variant_text = !empty($item['variant']) ? "<br><small style='color: #666;'>{$item['variant']}</small>" : "";
            $items_html .= "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee;'>
                    <strong>{$item['name']}</strong>{$variant_text}
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: center;'>₹{$item['price']}</td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($item_total, 2) . "</td>
            </tr>";
        }
    }

    $customer_phone = $shipping_details['phone'] ?? 'N/A';
    $customer_address = $shipping_details['address'] ?? 'N/A';

    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Hello $name,</h2>
        <p>Thank you for shopping with <strong>Goappalam</strong>. Your order has been successfully placed.</p>
        
        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FFC222;'>
            <h3 style='margin-top: 0; color: #1E1D23; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;'>Order Details</h3>
            <p style='margin: 5px 0;'><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
            <p style='margin: 5px 0;'><strong>Phone:</strong> $customer_phone</p>
            <p style='margin: 5px 0;'><strong>Shipping Address:</strong> $customer_address</p>
            <p style='margin: 10px 0;'><strong>Delivery OTP:</strong> <span style='font-size: 24px; color: #FFC222; font-weight: bold; letter-spacing: 2px;'>$otp</span></p>
        </div>

        <div style='margin: 20px 0;'>
            <h3 style='color: #1E1D23; font-size: 16px; text-transform: uppercase; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Order Items</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='color: #666; font-size: 12px; text-transform: uppercase;'>
                        <th style='text-align: left; padding: 10px 0;'>Item</th>
                        <th style='text-align: center; padding: 10px 0;'>Price</th>
                        <th style='text-align: center; padding: 10px 0;'>Qty</th>
                        <th style='text-align: right; padding: 10px 0;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $items_html
                </tbody>
            </table>
        </div>

        <div style='margin-top: 20px; border-top: 2px solid #eee; padding-top: 10px;'>
            <table style='width: 100%;'>
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Subtotal</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>₹" . number_format($subtotal, 2) . "</td>
                </tr>";
    
    if ($discount > 0) {
        $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #e53e3e;'>Discount</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold; color: #e53e3e;'>-₹" . number_format($discount, 2) . "</td>
                </tr>";
    }

    if ($tax > 0) {
        $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Tax / GST</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>₹" . number_format($tax, 2) . "</td>
                </tr>";
    }

    $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Shipping</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>" . ($shipping > 0 ? "₹" . number_format($shipping, 2) : "FREE") . "</td>
                </tr>
                <tr style='font-size: 18px;'>
                    <td style='padding: 15px 0; font-weight: black; text-transform: uppercase;'>Grand Total</td>
                    <td style='padding: 15px 0; text-align: right; font-weight: black; color: #FFC222;'>₹" . number_format($total, 2) . "</td>
                </tr>
            </table>
        </div>
        
        <p>Please keep the Delivery OTP ready when the delivery partner arrives. This OTP will be required to confirm and complete your order delivery.</p>
        <p>If you have any questions regarding your order, feel free to contact our support team.</p>
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best Regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    $attachments = $pdfData ? [['data' => $pdfData, 'name' => "Invoice_$order_id.pdf"]] : [];
    return sendEmail($email, $subject, $body, $attachments);
}

function send_online_payment_confirmation_email($email, $name, $order_id, $pdfData = null, $subtotal = 0, $shipping = 0, $tax = 0, $discount = 0, $total = 0, $items = [], $shipping_details = []) {
    $subject = "Your Goappalam Order is Paid & Confirmed";
    
    $items_html = "";
    if (is_array($items)) {
        foreach ($items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $variant_text = !empty($item['variant']) ? "<br><small style='color: #666;'>{$item['variant']}</small>" : "";
            $items_html .= "
            <tr>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee;'>
                    <strong>{$item['name']}</strong>{$variant_text}
                </td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: center;'>₹{$item['price']}</td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px 0; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($item_total, 2) . "</td>
            </tr>";
        }
    }

    $customer_phone = $shipping_details['phone'] ?? 'N/A';
    $customer_address = $shipping_details['address'] ?? 'N/A';

    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Hello $name,</h2>
        <p>Thank you for shopping with <strong>Goappalam</strong>. We have received your payment.</p>
        
        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FFC222;'>
            <h3 style='margin-top: 0; color: #1E1D23; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;'>Order Details</h3>
            <p style='margin: 5px 0;'><strong>Payment Status:</strong> PAID (Online)</p>
            <p style='margin: 5px 0;'><strong>Payment Method:</strong> Razorpay / Online</p>
            <p style='margin: 5px 0;'><strong>Phone:</strong> $customer_phone</p>
            <p style='margin: 5px 0;'><strong>Shipping Address:</strong> $customer_address</p>
        </div>

        <div style='margin: 20px 0;'>
            <h3 style='color: #1E1D23; font-size: 16px; text-transform: uppercase; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Order Items</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr style='color: #666; font-size: 12px; text-transform: uppercase;'>
                        <th style='text-align: left; padding: 10px 0;'>Item</th>
                        <th style='text-align: center; padding: 10px 0;'>Price</th>
                        <th style='text-align: center; padding: 10px 0;'>Qty</th>
                        <th style='text-align: right; padding: 10px 0;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $items_html
                </tbody>
            </table>
        </div>

        <div style='margin-top: 20px; border-top: 2px solid #eee; padding-top: 10px;'>
            <table style='width: 100%;'>
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Subtotal</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>₹" . number_format($subtotal, 2) . "</td>
                </tr>";
    
    if ($discount > 0) {
        $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #e53e3e;'>Discount</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold; color: #e53e3e;'>-₹" . number_format($discount, 2) . "</td>
                </tr>";
    }

    if ($tax > 0) {
        $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Tax / GST</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>₹" . number_format($tax, 2) . "</td>
                </tr>";
    }

    $body .= "
                <tr>
                    <td style='padding: 5px 0; color: #666;'>Shipping</td>
                    <td style='padding: 5px 0; text-align: right; font-weight: bold;'>" . ($shipping > 0 ? "₹" . number_format($shipping, 2) : "FREE") . "</td>
                </tr>
                <tr style='font-size: 18px;'>
                    <td style='padding: 15px 0; font-weight: black; text-transform: uppercase;'>Grand Total</td>
                    <td style='padding: 15px 0; text-align: right; font-weight: black; color: #FFC222;'>₹" . number_format($total, 2) . "</td>
                </tr>
            </table>
        </div>
        
        <p>Your order is now being processed and will be shipped soon. You will receive another email when your order is on its way.</p>
        <p>If you have any questions regarding your order, feel free to contact our support team.</p>
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best Regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    $attachments = $pdfData ? [['data' => $pdfData, 'name' => "Invoice_$order_id.pdf"]] : [];
    return sendEmail($email, $subject, $body, $attachments);
}

function send_admin_order_notification($order_id, $total, $customer_name, $items, $subtotal = 0, $shipping = 0, $tax = 0, $discount = 0, $payment_method = 'N/A', $shipping_details = []) {
    $admin_email = "goappalam@gmail.com";
    $subject = "New Order Received - Goappalam";
    
    $items_list = "";
    if (is_array($items)) {
        foreach ($items as $item) {
            $variant = !empty($item['variant']) ? " ({$item['variant']})" : "";
            $items_list .= "<li>{$item['name']}{$variant} x {$item['quantity']} - ₹{$item['price']}</li>";
        }
    }

    $customer_phone = $shipping_details['phone'] ?? 'N/A';
    $customer_email = $shipping_details['email'] ?? 'N/A';
    $customer_address = $shipping_details['address'] ?? 'N/A';
    $payment_text = strtoupper($payment_method);

    // Dynamic Admin URL
    $base_url = getenv('BASE_URL') ?: 'https://goappalam.in';
    $admin_url = rtrim($base_url, '/') . "/backend/admin/orders.php?action=view&id={$order_id}";

    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 500px; margin: 0 auto; padding: 20px; background-color: #f6f9fc;'>
        <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <p style='color: #666; font-size: 14px; margin-bottom: 5px;'>Admin Notification</p>
            <h1 style='color: #1E1D23; font-size: 28px; font-weight: bold; margin-top: 0; margin-bottom: 20px;'>New Order Received</h1>
            
            <div style='background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>
                <p style='margin: 0 0 10px 0;'><strong>Customer Details:</strong></p>
                <p style='margin: 0; font-size: 14px;'><strong>Name:</strong> {$customer_name}</p>
                <p style='margin: 0; font-size: 14px;'><strong>Phone:</strong> {$customer_phone}</p>
                <p style='margin: 0; font-size: 14px;'><strong>Address:</strong> {$customer_address}</p>
                <p style='margin: 10px 0 0 0; font-size: 14px;'><strong>Payment Method:</strong> <span style='color: #FFC222; font-weight: bold;'>{$payment_text}</span></p>
            </div>

            <div style='margin-bottom: 25px;'>
                <a href='{$admin_url}' style='background-color: #00639b; color: #ffffff; padding: 12px 25px; text-decoration: none; font-weight: bold; border-radius: 25px; display: inline-block;'>View Order in Admin Panel</a>
            </div>

            <div style='border-top: 1px solid #eee; padding-top: 20px;'>
                <table style='width: 100%; font-size: 14px; margin: 15px 0;'>
                    <tr>
                        <td>Subtotal:</td>
                        <td style='text-align: right;'>₹" . number_format($subtotal, 2) . "</td>
                    </tr>";
    
    if ($discount > 0) {
        $body .= "
                    <tr>
                        <td style='color: #e53e3e;'>Discount:</td>
                        <td style='text-align: right; color: #e53e3e;'>-₹" . number_format($discount, 2) . "</td>
                    </tr>";
    }

    if ($tax > 0) {
        $body .= "
                    <tr>
                        <td>Tax / GST:</td>
                        <td style='text-align: right;'>₹" . number_format($tax, 2) . "</td>
                    </tr>";
    }

    $body .= "
                    <tr>
                        <td>Shipping:</td>
                        <td style='text-align: right;'>" . ($shipping > 0 ? "₹" . number_format($shipping, 2) : "FREE") . "</td>
                    </tr>
                    <tr style='font-weight: bold; font-size: 16px;'>
                        <td style='padding-top: 10px;'>Grand Total:</td>
                        <td style='padding-top: 10px; text-align: right; color: #FFC222;'>₹" . number_format($total, 2) . "</td>
                    </tr>
                </table>

                <p style='font-size: 12px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 10px;'>Items Ordered</p>
                <ul style='font-size: 13px; color: #333; padding-left: 20px; margin-top: 0;'>
                    {$items_list}
                </ul>
            </div>
        </div>
    </div>";
    
    return sendEmail($admin_email, $subject, $body);
}

function send_signup_success_email($email, $name, $reset_token) {
    $subject = "Welcome to Goappalam – Your Account is Ready!";
    
    $reset_link = "https://goappalam.in/reset-password?token=" . $reset_token;
    
    $body = "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #1E1D23;'>Welcome $name,</h2>
        <p>Thank you for creating an account with <strong>Goappalam</strong>. We're excited to have you with us!</p>
        <p>You have been successfully logged in and your account is now active.</p>
        
        <p>To secure your account, we recommend setting a password by clicking the button below:</p>
        
        <div style='text-align: center; margin: 35px 0;'>
            <a href='{$reset_link}' style='background-color: #FFC222; color: #1E1D23; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 8px; display: inline-block;'>Set Your Password</a>
        </div>
        
        <p style='font-size: 13px; color: #666;'>This link will expire in 60 minutes.</p>
        
        <p>Thank you for choosing Goappalam.</p>
        <br>
        <p style='margin-bottom: 0;'>Best regards,</p>
        <p style='margin-top: 5px; font-weight: bold;'>Goappalam Team</p>
    </div>";
    
    return sendEmail($email, $subject, $body);
}
