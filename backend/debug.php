<?php
// Debug script disabled for production. Leaving a safe 404 response to avoid leaking system details.
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not Found']);
exit;
