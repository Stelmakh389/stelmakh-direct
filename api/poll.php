<?php
/*
 * Запасной режим для Telegram, если хостинг не пропускает вебхуки (в config.php: telegram.mode = 'poll').
 * Запускайте планировщиком хостинга каждую минуту:
 *   * * * * * php /путь/до/сайта/api/poll.php
 * Скрипт живёт ~55 секунд и сам забирает новые сообщения у Telegram (long polling), параллельно запущенные копии не мешают друг другу.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

if (PHP_SAPI !== 'cli') {
    $key = (string)cfg('setup_key', '');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) { http_response_code(403); exit; }
    header('Content-Type: text/plain; charset=utf-8');
}
if (!tg_enabled() || cfg('telegram.mode', 'webhook') !== 'poll') { echo "poll mode is off\n"; exit; }

@set_time_limit(0);
$lock = fopen(data_dir() . '/poll.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { echo "already running\n"; exit; }

$budget = PHP_SAPI === 'cli' ? 55 : 20;
$until = time() + $budget;
$offFile = data_dir() . '/tg_offset.json';
$off = (int)(json_read($offFile, [])['offset'] ?? 0);
$n = 0;
while (time() < $until - 2) {
    $wait = max(1, min(20, $until - time() - 3));
    $full = null;
    $params = ['timeout' => $wait, 'allowed_updates' => ['message', 'callback_query']];
    if ($off > 0) $params['offset'] = $off;
    $ups = tg_call('getUpdates', $params, $full);
    if ($ups === null) { sleep(3); continue; }
    foreach ($ups as $u) {
        if (!is_array($u) || !isset($u['update_id'])) continue;
        $off = (int)$u['update_id'] + 1;
        json_write($offFile, ['offset' => $off]);
        if (seen_once('tg:' . $u['update_id'])) { tg_process_update($u); $n++; }
    }
}
maybe_run_reminders();
echo date('Y-m-d H:i:s') . " processed: $n\n";
