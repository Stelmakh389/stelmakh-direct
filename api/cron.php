<?php
/*
 * Напоминания клиентам: накануне, за час, за 15 минут и в момент начала разбора, плюс одно напоминание тем, кто открыл бота, но не подтвердил время.
 * Запускайте каждые 5 минут планировщиком хостинга:
 *   php /путь/до/сайта/api/cron.php
 * или по ссылке (если хостинг умеет только URL):
 *   https://<ваш-сайт>/api/cron.php?key=<setup_key>
 * Без планировщика напоминания тоже уходят, но только когда кто-то пишет боту или оставляет заявку.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

if (PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
    $key = (string)cfg('setup_key', '');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) { http_response_code(403); exit; }
    header('Content-Type: text/plain; charset=utf-8');
}

$done = run_reminders();
echo date('Y-m-d H:i:s') . ' ' . ($done ? implode(', ', $done) : 'nothing to send') . "\n";
