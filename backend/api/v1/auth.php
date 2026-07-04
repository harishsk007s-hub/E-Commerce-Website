<?php
/**
 * OTP Auth API - DISABLED
 * Please use auth_login.php, auth_send_magic_link.php, etc.
 */
require_once 'api_init.php';

json_response(['error' => 'OTP system is disabled. Please use the new username/password login.'], 403);
