<?php
/* Демо-копия: заявка никуда не отправляется, просто отвечаем «ок», чтобы показать работу квиза. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok' => true, 'demo' => true], JSON_UNESCAPED_UNICODE);
