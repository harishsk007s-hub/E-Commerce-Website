<?php

function validate_checkout($data) {
    $errors = [];

    // 1. Full Name
    if (empty($data['full_name'])) {
        $errors['full_name'] = "Full name is required";
    }

    // 2. Phone Number (+91 format validation)
    if (empty($data['phone'])) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match('/^\+91[0-9]{10}$/', $data['phone'])) {
        $errors['phone'] = "Phone number must be in +91 format (e.g., +919876543210)";
    }

    // 3. Address Line 1
    if (empty($data['address1'])) {
        $errors['address1'] = "Address line 1 is required";
    }

    // 4. Address Line 2 (Optional)

    // 5. Landmark (Mandatory India delivery)
    if (empty($data['landmark'])) {
        $errors['landmark'] = "Landmark is required";
    }

    // 6. City
    if (empty($data['city'])) {
        $errors['city'] = "City is required";
    }

    // 7. State (India states validation)
    $states = ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];
    if (empty($data['state'])) {
        $errors['state'] = "State is required";
    } elseif (!in_array($data['state'], $states)) {
        $errors['state'] = "Invalid state selected";
    }

    // 8. PIN Code (6 digits validation)
    if (empty($data['pincode'])) {
        $errors['pincode'] = "PIN code is required";
    } elseif (!preg_match('/^[0-9]{6}$/', $data['pincode'])) {
        $errors['pincode'] = "PIN code must be exactly 6 digits";
    }

    // 9. Payment Method
    $payment_methods = ['razorpay', 'cod'];
    if (empty($data['payment_method'])) {
        $errors['payment_method'] = "Payment method is required";
    } elseif (!in_array($data['payment_method'], $payment_methods)) {
        $errors['payment_method'] = "Invalid payment method selected";
    }

    return $errors;
}
