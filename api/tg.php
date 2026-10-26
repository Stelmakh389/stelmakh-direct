<?php
/*
 * Вебхук Telegram-бота. Адрес регистрирует setup.php: https://<ваш-сайт>/api/tg.php
 * Telegram присылает заголовок X-Telegram-Bot-Api-Secret-Token – сверяем его с webhook_secret.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$secret = (string)cfg('webhook_secret', '');
if ($secret === '' || !hash_equals($secret, (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) {
    http_response_code(403);
    exit;
}

$u = json_decode((string)file_get_contents('php://input'), true);
http_response_code(200);
header('Content-Type: application/json');
echo '{}';
finish_request();
if (!is_array($u) || !isset($u['update_id']) || !seen_once('tg:' . $u['update_id'])) exit;

tg_process_update($u);
maybe_run_reminders();
