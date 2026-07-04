<?php
// Debug endpoint disabled for production. Returns 404 to avoid leaking any environment info.
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not Found']);
exit;
