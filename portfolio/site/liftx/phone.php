<?php
/* Демо-копия: механика показа номера работает, но настоящий номер не отдаём. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    echo json_encode(['token' => bin2hex(random_bytes(16))]);
    exit;
}
http_response_code(503);
echo json_encode(['error' => 'В демо-копии номер не показывается.'], JSON_UNESCAPED_UNICODE);
