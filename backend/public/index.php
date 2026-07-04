<?php
require_once __DIR__ . '/../includes/user-auth.php';

if (is_logged_in()) {
    header('Location: checkout.php');
} else {
    header('Location: login.php');
}
exit();
