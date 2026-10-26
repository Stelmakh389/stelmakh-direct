<?php
/*
 * Вебхук бота в Max. Адрес регистрирует setup.php: https://<ваш-сайт>/api/max.php
 * Max присылает заголовок X-Max-Bot-Api-Secret – сверяем его с webhook_secret.
 * События: bot_started (payload = id заявки из ссылки max.ru/<бот>?start=<id>), message_created, message_callback.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$secret = (string)cfg('webhook_secret', '');
if ($secret === '' || !hash_equals($secret, (string)($_SERVER['HTTP_X_MAX_BOT_API_SECRET'] ?? ''))) {
    http_response_code(403);
    exit;
}

$u = json_decode((string)file_get_contents('php://input'), true);
http_response_code(200);
header('Content-Type: application/json');
echo '{}';
finish_request();
if (!is_array($u) || empty($u['update_type'])) exit;

function max_user(array $x): array {
    $name = trim(($x['first_name'] ?? '') . ' ' . ($x['last_name'] ?? ''));
    if ($name === '') $name = (string)($x['name'] ?? '');
    return ['id' => (string)($x['user_id'] ?? ''), 'name' => $name, 'first' => (string)($x['first_name'] ?? ''), 'username' => (string)($x['username'] ?? '')];
}

try {
    $type = (string)$u['update_type'];

    if ($type === 'bot_started') {
        $user = max_user($u['user'] ?? []);
        $chat = $u['chat_id'] ?? null;
        if ($chat !== null && seen_once('max:start:' . $chat . ':' . ($u['timestamp'] ?? ''))) {
            client_start('max', $chat, $user, (string)($u['payload'] ?? ''));
        }
    } elseif ($type === 'message_created') {
        $msg = $u['message'] ?? [];
        $sender = $msg['sender'] ?? [];
        $chat = $msg['recipient']['chat_id'] ?? null;
        $chatType = (string)($msg['recipient']['chat_type'] ?? 'dialog');
        $mid = (string)($msg['body']['mid'] ?? '');
        /* Только личные диалоги и только сообщения людей */
        if ($chat !== null && $mid !== '' && $chatType === 'dialog' && empty($sender['is_bot']) && seen_once('max:msg:' . $mid)) {
            $user = max_user($sender);
            $text = (string)($msg['body']['text'] ?? '');
            $hasMedia = !empty($msg['body']['attachments']);
            $replyTo = (($msg['link']['type'] ?? '') === 'reply' && !empty($msg['link']['message']['mid'])) ? (string)$msg['link']['message']['mid'] : null;
            $vcf = '';
            foreach ((array)($msg['body']['attachments'] ?? []) as $att) {
                if (($att['type'] ?? '') === 'contact' && !empty($att['payload']['vcf_info'])) $vcf = (string)$att['payload']['vcf_info'];
            }
            if ($vcf !== '' && !is_admin('max', $chat)) {
                client_contact('max', $chat, $user, $vcf);
            } elseif (!admin_message('max', $chat, $user, $text, $mid, $replyTo, $hasMedia)) {
                client_message('max', $chat, $user, $text, $mid, $hasMedia);
            }
        }
    } elseif ($type === 'message_callback') {
        $cb = $u['callback'] ?? [];
        $cbId = (string)($cb['callback_id'] ?? '');
        $chat = $u['message']['recipient']['chat_id'] ?? null;
        $mid = isset($u['message']['body']['mid']) ? (string)$u['message']['body']['mid'] : null;
        if ($cbId !== '' && $chat !== null && seen_once('max:cb:' . $cbId)) {
            client_callback('max', $chat, max_user($cb['user'] ?? []), (string)($cb['payload'] ?? ''), $cbId, $mid);
        }
    }
} catch (Throwable $e) {
    log_line('MAX update error: ' . $e->getMessage() . ' @' . $e->getLine());
}

maybe_run_reminders();
