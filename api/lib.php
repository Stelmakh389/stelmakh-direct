<?php
/*
 * Общая библиотека: настройки, хранилище, API Telegram и Max, тексты бота, логика заявок.
 * Подключается из lead.php, tg.php, max.php, cron.php, setup.php. Напрямую не открывается.
 * PHP 7.4+, расширения curl (или allow_url_fopen), json, mbstring.
 */
declare(strict_types=1);

if (!defined('BOT_LIB')) define('BOT_LIB', 1);
date_default_timezone_set('Europe/Moscow');

/* ===================== Полифилы для PHP 7.4 ===================== */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return $n === '' || strncmp($h, $n, strlen($n)) === 0; }
}
function u_sub(string $s, int $start, int $len): string {
    return function_exists('mb_substr') ? mb_substr($s, $start, $len, 'UTF-8') : substr($s, $start, $len);
}
function u_len(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s); }

/* ===================== Настройки ===================== */
function cfg(string $key, $default = null) {
    static $c = null;
    if ($c === null) {
        $f = __DIR__ . '/config.php';
        $c = is_file($f) ? require $f : require __DIR__ . '/config.example.php';
        if (!is_array($c)) $c = [];
    }
    $v = $c;
    foreach (explode('.', $key) as $part) {
        if (!is_array($v) || !array_key_exists($part, $v)) return $default;
        $v = $v[$part];
    }
    return $v;
}

function owner_name(): string { return (string)cfg('owner.name', 'Андрей'); }
function owner_full(): string { return (string)cfg('owner.full', 'Андрей Стельмах'); }
function owner_dative(): string { return (string)cfg('owner.dative', 'Андрею'); }

function site_url(): string {
    $u = (string)cfg('site_url', '');
    if ($u === '') {
        $rt = json_read(data_dir() . '/runtime.json', []);
        $u = (string)($rt['site_url'] ?? '');
    }
    return rtrim($u, '/');
}

/* ===================== Хранилище (файлы JSON) ===================== */
function data_dir(): string {
    static $dir = null;
    if ($dir !== null) return $dir;
    $dir = rtrim((string)cfg('storage_dir', __DIR__ . '/data'), '/');
    foreach (['', '/leads', '/chats', '/locks'] as $sub) {
        if (!is_dir($dir . $sub)) @mkdir($dir . $sub, 0750, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    if (!is_file($dir . '/index.html')) @file_put_contents($dir . '/index.html', '');
    return $dir;
}

function json_read(string $file, $default) {
    if (!is_file($file)) return $default;
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return $default;
    $d = json_decode($raw, true);
    return $d === null ? $default : $d;
}

function json_write(string $file, $data): bool {
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return @rename($tmp, $file);
}

/** Выполнить $fn под файловой блокировкой $name (чтение-изменение-запись без гонок). */
function with_lock(string $name, callable $fn, bool $wait = true) {
    $fh = @fopen(data_dir() . '/locks/' . preg_replace('/[^a-z0-9_-]/i', '_', $name) . '.lock', 'c');
    if (!$fh) return $fn();
    if (!flock($fh, $wait ? LOCK_EX : (LOCK_EX | LOCK_NB))) { fclose($fh); return null; }
    try { return $fn(); } finally { flock($fh, LOCK_UN); fclose($fh); }
}

function log_line(string $msg): void {
    $f = data_dir() . '/bot.log';
    if (is_file($f) && filesize($f) > 1024 * 1024) @rename($f, $f . '.1');
    @file_put_contents($f, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

/* ---------- Заявки ---------- */
function lead_valid_id($id): bool { return is_string($id) && preg_match('/^[a-z0-9]{6,24}$/i', $id) === 1; }
function lead_new_id(): string { return 'b' . base_convert((string)time(), 10, 36) . bin2hex(random_bytes(3)); }
function lead_file(string $id): string { return data_dir() . '/leads/' . strtolower($id) . '.json'; }

function lead_get(string $id): ?array {
    if (!lead_valid_id($id)) return null;
    $l = json_read(lead_file($id), null);
    return is_array($l) ? $l : null;
}

/** Изменить заявку под блокировкой. $fn получает массив (или null, если заявки нет) и возвращает новый массив. */
function lead_update(string $id, callable $fn): ?array {
    if (!lead_valid_id($id)) return null;
    return with_lock('lead-' . strtolower($id), function () use ($id, $fn) {
        $cur = lead_get($id);
        $new = $fn($cur);
        if (!is_array($new)) return $cur;
        $new['id'] = strtolower($id);
        $new['updated'] = date('c');
        json_write(lead_file($id), $new);
        return $new;
    });
}

function lead_event(array &$lead, string $e, string $note = ''): void {
    $lead['history'][] = ['t' => date('c'), 'e' => $e] + ($note !== '' ? ['n' => u_sub($note, 0, 200)] : []);
    if (count($lead['history']) > 60) $lead['history'] = array_slice($lead['history'], -60);
}

function leads_recent(int $limit = 10, int $days = 30): array {
    $files = glob(data_dir() . '/leads/*.json') ?: [];
    $border = time() - $days * 86400;
    $list = [];
    foreach ($files as $f) {
        $m = (int)@filemtime($f);
        if ($m >= $border) $list[$f] = $m;
    }
    arsort($list);
    $out = [];
    foreach (array_keys($list) as $f) {
        $l = json_read($f, null);
        if (is_array($l)) $out[] = $l;
        if ($limit > 0 && count($out) >= $limit) break;
    }
    return $out;
}

/* ---------- Привязка чата клиента к заявке ---------- */
function chat_file(string $m, $chat): string { return data_dir() . '/chats/' . $m . '-' . preg_replace('/[^0-9-]/', '', (string)$chat) . '.json'; }
function chat_lead(string $m, $chat): ?string { $d = json_read(chat_file($m, $chat), []); return isset($d['lead']) ? (string)$d['lead'] : null; }
function chat_bind(string $m, $chat, string $leadId): void { json_write(chat_file($m, $chat), ['lead' => strtolower($leadId), 't' => date('c')]); }

/* ---------- Администраторы ---------- */
function admins(): array {
    $saved = json_read(data_dir() . '/admins.json', []);
    $out = [];
    foreach (['telegram', 'max'] as $m) {
        $ids = array_merge((array)cfg('admins.' . $m, []), array_keys((array)($saved[$m] ?? [])));
        $out[$m] = array_values(array_unique(array_map('strval', array_filter($ids, function ($x) { return (string)$x !== ''; }))));
    }
    return $out;
}
function is_admin(string $m, $chat): bool { return in_array((string)$chat, admins()[$m] ?? [], true); }
function admin_add(string $m, $chat, string $name): void {
    with_lock('admins', function () use ($m, $chat, $name) {
        $f = data_dir() . '/admins.json';
        $d = json_read($f, []);
        $d[$m][(string)$chat] = ['name' => $name, 't' => date('c')];
        json_write($f, $d);
    });
}

/* ---------- Какое сообщение администратора к какой заявке относится ---------- */
function map_put(string $m, $chat, string $mid, string $leadId): void {
    with_lock('replymap', function () use ($m, $chat, $mid, $leadId) {
        $f = data_dir() . '/replymap.json';
        $d = json_read($f, []);
        $d[$m . ':' . $chat . ':' . $mid] = $leadId;
        if (count($d) > 5000) $d = array_slice($d, -4000, null, true);
        json_write($f, $d);
    });
}
function map_get(string $m, $chat, string $mid): ?string {
    $d = json_read(data_dir() . '/replymap.json', []);
    $k = $m . ':' . $chat . ':' . $mid;
    return isset($d[$k]) ? (string)$d[$k] : null;
}

/** true – событие видим впервые (защита от повторной доставки вебхука). */
function seen_once(string $key): bool {
    $r = with_lock('seen', function () use ($key) {
        $f = data_dir() . '/seen.json';
        $d = json_read($f, []);
        if (isset($d[$key])) return false;
        $d[$key] = time();
        if (count($d) > 800) $d = array_slice($d, -600, null, true);
        json_write($f, $d);
        return true;
    });
    return $r !== false;
}

/* ===================== HTTP ===================== */
function http_json(string $url, string $method = 'GET', ?array $body = null, array $headers = [], int $timeout = 10, string $ca = ''): array {
    $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers[] = 'Accept: application/json';
    if ($payload !== null) $headers[] = 'Content-Type: application/json';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opt = [
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => $timeout,
        ];
        if ($payload !== null) $opt[CURLOPT_POSTFIELDS] = $payload;
        if ($ca !== '') $opt[CURLOPT_CAINFO] = $ca;
        curl_setopt_array($ch, $opt);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = $res === false ? curl_error($ch) : '';
        curl_close($ch);
    } else {
        $ctx = ['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'timeout' => $timeout, 'ignore_errors' => true]];
        if ($payload !== null) $ctx['http']['content'] = $payload;
        if ($ca !== '') $ctx['ssl'] = ['cafile' => $ca];
        $res = @file_get_contents($url, false, stream_context_create($ctx));
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s?/', $http_response_header[0], $mm)) $code = (int)$mm[1];
        $err = $res === false ? 'connection failed' : '';
    }
    $data = is_string($res) ? json_decode($res, true) : null;
    return ['code' => $code, 'data' => is_array($data) ? $data : null, 'error' => $err, 'raw' => is_string($res) ? u_sub($res, 0, 500) : ''];
}

/* ===================== Telegram Bot API ===================== */
function tg_enabled(): bool { return (string)cfg('telegram.token', '') !== ''; }
function tg_call(string $method, array $params, ?array &$full = null): ?array {
    if (!tg_enabled()) return null;
    $base = rtrim((string)cfg('telegram.api', 'https://api.telegram.org'), '/');
    $to = isset($params['timeout']) ? (int)$params['timeout'] + 10 : 10;
    $r = http_json($base . '/bot' . cfg('telegram.token') . '/' . $method, 'POST', $params, [], $to);
    /* Хостинг иногда не может соединиться с Telegram – один повтор через секунду (кроме опроса, он и так повторяет) */
    if ($r['code'] === 0 && $method !== 'getUpdates') { sleep(1); $r = http_json($base . '/bot' . cfg('telegram.token') . '/' . $method, 'POST', $params, [], $to); }
    $full = $r;
    if (!empty($r['data']['ok'])) return is_array($r['data']['result']) ? $r['data']['result'] : ['value' => $r['data']['result']];
    log_line('TG ' . $method . ' ' . $r['code'] . ' ' . ($r['error'] ?: ($r['data']['description'] ?? $r['raw'])));
    return null;
}

/* ===================== Max Bot API ===================== */
function max_enabled(): bool { return (string)cfg('max.token', '') !== ''; }
function max_call(string $method, string $path, array $query = [], ?array $body = null, ?array &$full = null): ?array {
    if (!max_enabled()) return null;
    $base = rtrim((string)cfg('max.api', 'https://platform-api2.max.ru'), '/');
    $url = $base . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $r = http_json($url, $method, $body, ['Authorization: ' . cfg('max.token')], 10, (string)cfg('max.ca_file', ''));
    $full = $r;
    if ($r['code'] >= 200 && $r['code'] < 300 && is_array($r['data'])) return $r['data'];
    log_line('MAX ' . $method . ' ' . $path . ' ' . $r['code'] . ' ' . ($r['error'] ?: $r['raw']));
    return null;
}

/* ===================== Отправка: общий интерфейс для обоих мессенджеров ===================== */
/*
 * Кнопки: [[['text' => 'Подтверждаю', 'data' => 'ok:id'], ['text' => 'Сайт', 'url' => 'https://…']], …]
 */
function kb_tg(array $rows): ?array {
    if (!$rows) return null;
    $out = [];
    foreach ($rows as $row) {
        $r = [];
        foreach ($row as $b) { if (!empty($b['contact'])) continue; $r[] = isset($b['url']) ? ['text' => $b['text'], 'url' => $b['url']] : ['text' => $b['text'], 'callback_data' => $b['data']]; }
        if ($r) $out[] = $r;
    }
    return $out ? ['inline_keyboard' => $out] : null;
}
function kb_max(array $rows): array {
    if (!$rows) return [];
    $out = [];
    foreach ($rows as $row) {
        $r = [];
        foreach ($row as $b) $r[] = !empty($b['contact']) ? ['type' => 'request_contact', 'text' => $b['text']] : (isset($b['url']) ? ['type' => 'link', 'text' => $b['text'], 'url' => $b['url']] : ['type' => 'callback', 'text' => $b['text'], 'payload' => $b['data']]);
        $out[] = $r;
    }
    return [['type' => 'inline_keyboard', 'payload' => ['buttons' => $out]]];
}

/** Отправить сообщение. Возвращает id сообщения (строкой) или null. */
function bot_send(string $m, $chat, string $html, array $buttons = [], ?string $replyTo = null): ?string {
    if ($m === 'telegram') {
        $p = ['chat_id' => $chat, 'text' => u_sub($html, 0, 4096), 'parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]];
        if ($kb = kb_tg($buttons)) $p['reply_markup'] = $kb;
        if ($replyTo !== null) $p['reply_parameters'] = ['message_id' => (int)$replyTo, 'allow_sending_without_reply' => true];
        $r = tg_call('sendMessage', $p);
        return isset($r['message_id']) ? (string)$r['message_id'] : null;
    }
    $body = ['text' => u_sub($html, 0, 4000), 'format' => 'html'];
    if ($att = kb_max($buttons)) $body['attachments'] = $att;
    if ($replyTo !== null) $body['link'] = ['type' => 'reply', 'mid' => $replyTo];
    $r = max_call('POST', 'messages', ['chat_id' => $chat, 'disable_link_preview' => 'true'], $body);
    return isset($r['message']['body']['mid']) ? (string)$r['message']['body']['mid'] : null;
}

function bot_edit(string $m, $chat, string $mid, string $html, array $buttons = []): bool {
    if ($m === 'telegram') {
        $p = ['chat_id' => $chat, 'message_id' => (int)$mid, 'text' => u_sub($html, 0, 4096), 'parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]];
        if ($kb = kb_tg($buttons)) $p['reply_markup'] = $kb;
        $full = null;
        $r = tg_call('editMessageText', $p, $full);
        /* «message is not modified» – текст тот же, это не ошибка */
        return $r !== null || strpos((string)($full['data']['description'] ?? ''), 'not modified') !== false;
    }
    $body = ['text' => u_sub($html, 0, 4000), 'format' => 'html', 'attachments' => kb_max($buttons)];
    return max_call('PUT', 'messages', ['message_id' => $mid], $body) !== null;
}

function bot_delete(string $m, $chat, string $mid): bool {
    if ($m === 'telegram') return tg_call('deleteMessage', ['chat_id' => $chat, 'message_id' => (int)$mid]) !== null;
    return max_call('DELETE', 'messages', ['message_id' => $mid]) !== null;
}

function bot_answer(string $m, string $cbId, string $text = ''): void {
    if ($cbId === '') return;
    if ($m === 'telegram') { tg_call('answerCallbackQuery', ['callback_query_id' => $cbId, 'text' => $text]); return; }
    max_call('POST', 'answers', ['callback_id' => $cbId], ['notification' => $text !== '' ? $text : 'Готово']);
}

/* ===================== Форматирование ===================== */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function messenger_name(string $m): string { return $m === 'max' ? 'Max' : 'Telegram'; }

function slot_label(?string $date, ?string $time): string {
    if (!$date || !$time) return '';
    $ts = strtotime($date . ' 12:00');
    if (!$ts) return '';
    $wd = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
    $mon = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    return $wd[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' ' . $mon[(int)date('n', $ts) - 1] . ', ' . $time;
}
function slot_ts(array $lead): ?int {
    if (empty($lead['slot_date']) || empty($lead['slot_time'])) return null;
    $t = strtotime($lead['slot_date'] . ' ' . $lead['slot_time'] . ':00');
    return $t ?: null;
}
function slot_future(array $lead, int $marginMin = 30): bool { $t = slot_ts($lead); return $t !== null && $t > time() + $marginMin * 60; }

function booking_slots(): array { return array_values((array)cfg('booking.slots', ['10:00', '11:30', '14:00', '16:00', '18:30'])); }
function day_label(string $iso): string { $l = slot_label($iso, '00:00'); return substr($l, 0, (int)strrpos($l, ',')); }
function ymd_iso(string $ymd): string { return substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2); }

/**
 * Занятые слоты: «Y-m-d H:i» => id заявки.
 * Занимают время подтверждённые записи и те, кто открыл бота, но ещё не подтвердил (держим 3 часа).
 */
function booking_busy(string $exceptId = ''): array {
    $busy = [];
    $now = time();
    foreach (glob(data_dir() . '/leads/*.json') ?: [] as $f) {
        $l = json_read($f, null);
        if (!is_array($l) || ($l['id'] ?? '') === $exceptId) continue;
        $ts = slot_ts($l);
        if ($ts === null || $ts < $now - 3600) continue;
        $st = (string)($l['status'] ?? '');
        $key = $l['slot_date'] . ' ' . $l['slot_time'];
        if ($st === 'confirmed') { $busy[$key] = (string)$l['id']; continue; }
        if (($st === 'started' || $st === 'new') && !empty($l['client'])) {
            $u = strtotime((string)($l['updated'] ?? '')) ?: 0;
            if ($now - $u < 3 * 3600) $busy[$key] = (string)$l['id'];
        }
    }
    return $busy;
}

/** Можно ли записаться на это время: рабочий день, слот из сетки, не раньше чем через N часов, никем не занят. */
function slot_free(string $date, string $time, ?array $busy = null, string $exceptId = ''): bool {
    $ts = strtotime($date . ' ' . $time . ':00');
    if (!$ts || !in_array($time, booking_slots(), true)) return false;
    if (!cfg('booking.weekends', false) && in_array((int)date('w', $ts), [0, 6], true)) return false;
    if ($ts < time() + (int)cfg('booking.min_hours', 2) * 3600) return false;
    if ($ts > time() + ((int)cfg('booking.horizon', 30) + 1) * 86400) return false;
    if ($busy === null) $busy = booking_busy($exceptId);
    return !isset($busy[$date . ' ' . $time]);
}

/** Дни со свободным временем на horizon дней вперёд, начиная с сегодня: [['iso','label','slots'=>[...]]] */
function booking_free_days(string $exceptId = ''): array {
    $busy = booking_busy($exceptId);
    $out = [];
    $d = strtotime('today 12:00');
    $horizon = (int)cfg('booking.horizon', 30);
    for ($i = 0; $i <= $horizon; $i++, $d = strtotime('+1 day', $d)) {
        $iso = date('Y-m-d', $d);
        $free = [];
        foreach (booking_slots() as $s) if (slot_free($iso, $s, $busy)) $free[] = $s;
        if ($free) $out[] = ['iso' => $iso, 'label' => day_label($iso), 'slots' => $free];
    }
    return $out;
}

function status_label(string $s): string {
    $map = [
        'new'       => 'Ждём, когда клиент откроет бота',
        'started'   => 'Открыл бота, время ещё не подтвердил',
        'confirmed' => 'Время подтверждено',
        'cancelled' => 'Запись отменена клиентом',
    ];
    return $map[$s] ?? $s;
}
function status_mark(string $s): string {
    $map = ['new' => '⏳', 'started' => '👀', 'confirmed' => '✅', 'cancelled' => '✖️'];
    return $map[$s] ?? '•';
}

/** Ник и ссылка на профиль клиента: ['@ivan', 'https://t.me/ivan'] или ['', ''] */
function client_profile(array $c): array {
    $u = ltrim(trim((string)($c['username'] ?? '')), '@');
    if ($u === '') return ['', ''];
    return ['@' . $u, (($c['m'] ?? '') === 'max' ? 'https://max.ru/' : 'https://t.me/') . rawurlencode($u)];
}

function client_display(array $lead, bool $link = true): string {
    $c = $lead['client'] ?? null;
    if (!$c) return 'ещё не открыл бота';
    $name = trim((string)($c['name'] ?? '')) ?: 'Без имени';
    $s = h($name);
    if ($link && ($c['m'] ?? '') === 'telegram' && !empty($c['user_id'])) $s = '<a href="tg://user?id=' . (int)$c['user_id'] . '">' . $s . '</a>';
    return $s . ' · ' . messenger_name((string)$c['m']);
}

/** Коротко для уведомлений: «Иван (@ivan, Max)» */
function client_short(array $lead): string {
    $c = $lead['client'] ?? [];
    $name = trim((string)($c['name'] ?? '')) ?: 'Клиент';
    $extra = [];
    if (!empty($c['username'])) $extra[] = '@' . ltrim((string)$c['username'], '@');
    if (($c['m'] ?? '') === 'max') $extra[] = 'Max';
    return h($name) . ($extra ? ' (' . h(implode(', ', $extra)) . ')' : '');
}

/* ---------- Карточка для владельца ---------- */
function admin_card(array $lead): array {
    $st = (string)($lead['status'] ?? 'new');
    $src = ['quiz' => 'квиз на сайте', 'form' => 'форма записи на сайте', 'bot' => 'написал в бота сам'];
    $c = $lead['client'] ?? null;
    $t = status_mark($st) . ' <b>Заявка #' . h($lead['id']) . '</b>' . "\n";
    $t .= '<b>Статус:</b> ' . h(status_label($st)) . "\n";
    $when = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    $t .= '<b>Разбор:</b> ' . ($when ? h($when) . ' МСК' : 'время не выбрано') . "\n";
    $t .= '<b>Клиент:</b> ' . client_display($lead) . "\n";
    if ($c) {
        [$nick, $url] = client_profile($c);
        $t .= '<b>Ник:</b> ' . ($nick !== '' ? '<a href="' . h($url) . '">' . h($nick) . '</a>' : (($c['m'] ?? '') === 'max'
            ? '<i>в Max у клиента нет публичного ника, ссылку на профиль мессенджер не даёт. Пишите ответом на эту карточку – сообщение придёт ему в Max от имени бота.' . (empty($c['phone']) ? ' Клиенту предложена кнопка «Поделиться номером» – как нажмёт, номер появится здесь' : '') . '</i>'
            : '<i>ника нет – пишите ответом на эту карточку</i>')) . "\n";
        if (!empty($c['phone'])) $t .= '<b>Телефон:</b> <code>+' . h($c['phone']) . '</code> (нажмите, чтобы скопировать; в ' . messenger_name((string)$c['m']) . ' клиента можно найти по номеру)' . "\n";
        if (!empty($c['user_id'])) $t .= '<b>ID в ' . messenger_name((string)$c['m']) . ':</b> <code>' . h($c['user_id']) . '</code>' . "\n";
    }
    if (!empty($lead['site'])) $t .= '<b>Сайт:</b> ' . h($lead['site']) . "\n";
    $t .= '<b>Откуда:</b> ' . h($src[$lead['source'] ?? ''] ?? ($lead['source'] ?? '–'));
    if (!empty($lead['marks'])) $t .= ' · ' . h(http_build_query($lead['marks'], '', ' · '));
    $t .= "\n";
    if (!empty($lead['answers'])) {
        $t .= "\n<b>Ответы в квизе</b>\n";
        foreach ($lead['answers'] as $a) $t .= '• ' . h($a['q'] ?? '') . ' – <b>' . h($a['a'] ?? '') . "</b>\n";
    }
    $f = $lead['forecast'] ?? [];
    if ($f) {
        $parts = [];
        if (!empty($f['cpc'])) $parts[] = 'клик ' . $f['cpc'];
        if (!empty($f['leads'])) $parts[] = 'заявок ' . $f['leads'];
        if (!empty($f['cpl'])) $parts[] = 'заявка ' . $f['cpl'];
        if ($parts) $t .= "\n<b>Прогноз:</b> " . h(implode(', ', $parts)) . "\n";
    }
    $t .= "\n" . ($c
        ? '<i>Ответьте на эту карточку – текст уйдёт клиенту (ссылку на созвон тоже присылайте так). Перенос на любое время: ответьте «перенос 28.10 15:30».</i>'
        : '<i>Написать клиенту можно будет, когда он откроет бота.</i>');
    $btn = [];
    if ($c) {
        $id = $lead['id'];
        $row = [['text' => $when && $st !== 'cancelled' ? '🔁 Перенести' : '📅 Назначить время', 'data' => 'ar:' . $id]];
        if ($when && $st !== 'cancelled') $row[] = ['text' => '✖️ Отменить', 'data' => 'ax:' . $id];
        $btn[] = $row;
        [$nick, $url] = client_profile($c);
        if ($nick !== '') $btn[] = [['text' => 'Профиль ' . $nick . ' в ' . messenger_name((string)$c['m']), 'url' => $url]];
    }
    $btn[] = [['text' => '🗑 Удалить заявку', 'data' => 'ak:' . $lead['id']]];
    return [$t, $btn];
}

/* ---------- Сообщение для клиента ---------- */
function client_card(array $lead): array {
    $id = $lead['id'];
    $when = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    $st = (string)($lead['status'] ?? 'new');
    $t = '<b>Онлайн-разбор вашей рекламы</b>' . "\n";
    $t .= 'Когда: <b>' . h($when) . '</b> по Москве' . "\n";
    $t .= 'Формат: видеозвонок на 30 минут, Телемост или Zoom' . "\n";
    $f = $lead['forecast'] ?? [];
    if (!empty($f['cpl'])) $t .= 'Ориентир из квиза: заявка ' . h($f['cpl']) . (!empty($f['leads']) ? ', ' . h($f['leads']) . ' заявок в месяц' : '') . "\n";
    if ($st === 'confirmed') {
        $t .= "\n✅ Время подтверждено. Напомню накануне, за час и за 15 минут до начала, а ссылку на созвон пришлю сюда.\nЕсли появится вопрос – пишите прямо сюда, отвечу лично.";
        return [$t, array_merge([[['text' => 'Перенести', 'data' => 'rs:' . $id], ['text' => 'Отменить', 'data' => 'cx:' . $id]]], contact_row($lead), owner_row($lead))];
    }
    $t .= "\nПодтвердите, пожалуйста, время – и я забронирую его за вами.";
    return [$t, array_merge([
        [['text' => '✅ Подтверждаю', 'data' => 'ok:' . $id]],
        [['text' => 'Другое время', 'data' => 'rs:' . $id], ['text' => 'Отменить', 'data' => 'cx:' . $id]],
    ], contact_row($lead), owner_row($lead))];
}

/** В Max нет ссылок на профиль – без номера телефона владелец не сможет связаться с клиентом напрямую. */
function phone_gate(array $lead): bool {
    $c = $lead['client'] ?? [];
    return ($c['m'] ?? '') === 'max' && empty($c['phone']);
}
function contact_row(array $lead): array {
    return phone_gate($lead) ? [[['text' => '📱 Поделиться номером для связи', 'contact' => true]]] : [];
}
/** Кнопка «Написать владельцу лично»: ссылки на личные профили задаются в config.php (owner.max_url, owner.tg_url). */
function owner_row(array $lead): array {
    $m = (string)($lead['client']['m'] ?? '');
    $url = (string)cfg($m === 'max' ? 'owner.max_url' : 'owner.tg_url', '') ?: (string)cfg($m === 'max' ? 'owner.tg_url' : 'owner.max_url', '');
    if (!preg_match('#^https://#', $url)) return [];
    return [[['text' => '💬 Написать ' . owner_dative() . ' лично', 'url' => $url]]];
}
function phone_ask(array $lead): array {
    $when = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    return ['📱 Чтобы закрепить время <b>' . h($when) . '</b> МСК, поделитесь номером телефона – нажмите кнопку ниже.' . "\n\n"
        . 'Я напишу или позвоню в день разбора и пришлю ссылку на созвон. Номер нужен только для этого.',
        array_merge(contact_row($lead), [[['text' => 'Другое время', 'data' => 'rs:' . $lead['id']]]], owner_row($lead))];
}

/** Достать номер из vCard: «TEL;TYPE=cell:+79991234567» → 79991234567 */
function phone_from_vcf(string $vcf): string {
    if (!preg_match('/TEL[^:\r\n]*:([+\d\s()\-]+)/i', $vcf, $mm)) return '';
    $d = preg_replace('/\D+/', '', $mm[1]);
    if (strlen($d) === 11 && $d[0] === '8') $d = '7' . substr($d, 1);
    if (strlen($d) === 10) $d = '7' . $d;
    return strlen($d) >= 10 && strlen($d) <= 15 ? $d : '';
}

/** Клиент поделился номером (кнопка «Поделиться номером» в Max). */
function client_contact(string $m, $chat, array $u, string $vcf): void {
    $phone = phone_from_vcf($vcf);
    $id = chat_lead($m, $chat);
    if ($phone === '' || !$id || !lead_get($id)) { bot_send($m, $chat, 'Не получилось прочитать номер. Ничего страшного – я напишу вам прямо сюда.'); return; }
    $lead = lead_update($id, function ($l) use ($phone) { if (!$l) return null; $l['client']['phone'] = $phone; lead_event($l, 'phone', '+' . $phone); return $l; });
    $confirmed = false;
    if ($lead && !empty($lead['pending_ok']) && ($lead['status'] ?? '') !== 'confirmed' && slot_future($lead, 0) && slot_free((string)$lead['slot_date'], (string)$lead['slot_time'], null, $id)) {
        $lead = lead_update($id, function ($l) { $l['status'] = 'confirmed'; $l['confirmed_at'] = date('c'); unset($l['reminded'], $l['pending_ok']); lead_event($l, 'confirmed'); return $l; });
        $confirmed = true;
    }
    if ($confirmed) {
        [$t, $b] = client_card($lead);
        bot_send($m, $chat, 'Спасибо, номер сохранил. Напишу или позвоню только по поводу разбора.' . "\n\n" . $t, $b);
    } else if ($lead && !empty($lead['pending_ok']) && ($lead['status'] ?? '') !== 'confirmed') {
        bot_send($m, $chat, 'Спасибо, номер сохранил. Но выбранное время уже недоступно – выберите другое (время московское):', days_keyboard($id));
    } else {
        bot_send($m, $chat, 'Спасибо, номер сохранил. Напишу или позвоню только по поводу разбора.');
    }
    cards_refresh($id);
    if ($lead) {
        notify_admins($id, '📱 ' . client_short($lead) . ' поделился номером: <code>+' . h($phone) . '</code>');
        if ($confirmed) notify_admins($id, '✅ ' . client_short($lead) . ': время подтверждено – <b>' . h(slot_label($lead['slot_date'], $lead['slot_time'])) . '</b> МСК');
    }
}

/** Дни со свободным временем, по 6 на страницу, с листанием. $admin – клавиатура переноса для владельца. */
function days_keyboard(string $id, int $page = 0, bool $admin = false): array {
    $days = booking_free_days($id);
    $per = max(2, (int)cfg('booking.page', 6));
    $pages = max(1, (int)ceil(count($days) / $per));
    $page = max(0, min($page, $pages - 1));
    $rows = [];
    $row = [];
    foreach (array_slice($days, $page * $per, $per) as $d) {
        $row[] = ['text' => $d['label'], 'data' => ($admin ? 'ad:' : 'rd:') . $id . ':' . str_replace('-', '', $d['iso'])];
        if (count($row) === 2) { $rows[] = $row; $row = []; }
    }
    if ($row) $rows[] = $row;
    $nav = [];
    if ($page > 0) $nav[] = ['text' => '← Раньше', 'data' => ($admin ? 'ap:' : 'rp:') . $id . ':' . ($page - 1)];
    if ($page < $pages - 1) $nav[] = ['text' => 'Позже →', 'data' => ($admin ? 'ap:' : 'rp:') . $id . ':' . ($page + 1)];
    if ($nav) $rows[] = $nav;
    return $rows;
}
function slots_keyboard(string $id, string $ymd, bool $admin = false): array {
    $iso = ymd_iso($ymd);
    $busy = booking_busy($id);
    $rows = [];
    $row = [];
    foreach (booking_slots() as $s) {
        if (!slot_free($iso, $s, $busy)) continue;
        $row[] = ['text' => $s, 'data' => ($admin ? 'at:' : 'rt:') . $id . ':' . $ymd . ':' . str_replace(':', '', $s)];
        if (count($row) === 3) { $rows[] = $row; $row = []; }
    }
    if ($row) $rows[] = $row;
    $rows[] = [['text' => '← Другой день', 'data' => ($admin ? 'ar:' : 'rs:') . $id]];
    return $rows;
}

function policy_line(): string {
    $u = site_url();
    if ($u === '') return 'Как обрабатываются ваши данные – в Политике обработки персональных данных на сайте.';
    return 'Как обрабатываются ваши данные – в <a href="' . h($u . '/politika.html') . '">Политике обработки персональных данных</a>.';
}

/* ===================== Уведомления владельцу ===================== */
function lead_worth_card(array $lead): bool {
    return ($lead['source'] ?? '') !== 'bot' || !empty($lead['slot_date']) || !empty($lead['msgs']);
}

/** Создать или обновить карточку заявки у каждого администратора. */
function cards_refresh(string $leadId): void {
    $lead = lead_get($leadId);
    if (!$lead || !lead_worth_card($lead)) return;
    [$text, $btn] = admin_card($lead);
    $cards = (array)($lead['cards'] ?? []);
    $changed = false;
    foreach (admins() as $m => $chats) {
        if (($m === 'telegram' && !tg_enabled()) || ($m === 'max' && !max_enabled())) continue;
        foreach ($chats as $chat) {
            $key = $m . ':' . $chat;
            if (isset($cards[$key]) && bot_edit($m, $chat, (string)$cards[$key], $text, $btn)) continue;
            $mid = bot_send($m, $chat, $text, $btn);
            if ($mid !== null) { $cards[$key] = $mid; map_put($m, $chat, $mid, $lead['id']); $changed = true; }
        }
    }
    if ($changed) lead_update($leadId, function ($l) use ($cards) { if (!$l) return null; $l['cards'] = array_merge((array)($l['cards'] ?? []), $cards); return $l; });
}

/** Короткое событие ответом на карточку – чтобы пришло уведомление. */
function notify_admins(string $leadId, string $html): void {
    $lead = lead_get($leadId);
    if (!$lead) return;
    $cards = (array)($lead['cards'] ?? []);
    foreach (admins() as $m => $chats) {
        if (($m === 'telegram' && !tg_enabled()) || ($m === 'max' && !max_enabled())) continue;
        foreach ($chats as $chat) {
            $mid = bot_send($m, $chat, $html, [], $cards[$m . ':' . $chat] ?? null);
            if ($mid !== null) map_put($m, $chat, $mid, $lead['id']);
        }
    }
}

/* ===================== Сценарий клиента ===================== */
function user_name(array $u): string { return trim((string)($u['name'] ?? '')); }

function bind_client(string $id, string $m, $chat, array $u): ?array {
    chat_bind($m, $chat, $id);
    return lead_update($id, function ($l) use ($id, $m, $chat, $u) {
        if (!$l) $l = ['id' => $id, 'created' => date('c'), 'source' => 'bot', 'status' => 'new'];
        $phone = (string)($l['client']['phone'] ?? '');
        $l['client'] = ['m' => $m, 'chat' => (string)$chat, 'user_id' => (string)($u['id'] ?? ''), 'name' => user_name($u), 'username' => (string)($u['username'] ?? '')];
        if ($phone !== '') $l['client']['phone'] = $phone;
        $l['messenger'] = $m;
        if (($l['status'] ?? 'new') === 'new') { $l['status'] = 'started'; $l['started_at'] = date('c'); lead_event($l, 'started', messenger_name($m)); }
        return $l;
    });
}

/** Клиент нажал «Старт» (с id заявки или без) или прислал код заявки. */
function client_start(string $m, $chat, array $u, string $payload): void {
    $payload = strtolower(trim($payload));
    $id = null;
    if (lead_valid_id($payload)) {
        $id = $payload;
        /* Заявка с сайта может прийти на долю секунды позже, чем «Старт» */
        if (!lead_get($id)) { usleep(1500000); }
    } else {
        $cur = chat_lead($m, $chat);
        $l = $cur ? lead_get($cur) : null;
        if ($l && ($l['status'] ?? '') !== 'cancelled' && slot_future($l)) $id = $cur;
    }
    if ($id === null) $id = lead_new_id();
    $lead = bind_client($id, $m, $chat, $u);
    if (!$lead) return;

    $name = trim((string)($u['first'] ?? '')) ?: user_name($u);
    $hello = 'Здравствуйте' . ($name !== '' ? ', ' . h($name) : '') . '! Это ' . h(owner_full()) . ', я настраиваю Яндекс.Директ.';
    if (!slot_future($lead)) {
        $txt = $hello . "\n\nЗапишу вас на бесплатный онлайн-разбор рекламы – 30 минут по видеосвязи. Выберите удобный день:\n\n<i>" . policy_line() . '</i>';
        if (!empty($lead['slot_date'])) $txt = $hello . "\n\nВремя, которое вы выбирали, уже прошло. Выберите новый день:";
        bot_send($m, $chat, $txt, days_keyboard($id));
    } else {
        [$t, $b] = client_card($lead);
        bot_send($m, $chat, $hello . "\n\n" . $t, $b);
    }
    cards_refresh($id);
}

/** Нажатие кнопки клиентом. */
function client_callback(string $m, $chat, array $u, string $data, string $cbId, ?string $mid): void {
    if (preg_match('/^a[a-z]:/', $data) && is_admin($m, $chat)) { admin_callback($m, $chat, $data, $cbId, $mid); return; }
    $parts = explode(':', $data);
    $act = $parts[0] ?? '';
    $id = strtolower($parts[1] ?? '');
    $lead = lead_get($id);
    if (!$lead || (string)($lead['client']['chat'] ?? '') !== (string)$chat) {
        /* Кнопка из старого сообщения в этом же чате (клиент переходил в другой мессенджер) – привяжем обратно */
        if ($lead && chat_lead($m, $chat) === $lead['id']) { $lead = bind_client($id, $m, $chat, $u); }
        else { bot_answer($m, $cbId, 'Запись не найдена – нажмите /start'); return; }
    }
    $show = function (string $text, array $btn) use ($m, $chat, $mid) {
        if ($mid === null || !bot_edit($m, $chat, $mid, $text, $btn)) bot_send($m, $chat, $text, $btn);
    };

    if ($act === 'ok') {
        if (!slot_future($lead, 0)) { bot_answer($m, $cbId, 'Это время уже прошло'); $show('Время уже прошло – выберите новый день:', days_keyboard($id)); return; }
        if (($lead['status'] ?? '') !== 'confirmed' && isset(booking_busy($id)[$lead['slot_date'] . ' ' . $lead['slot_time']])) {
            bot_answer($m, $cbId, 'Это время уже заняли');
            $show('Пока вы выбирали, это время заняли. Выберите другое (время московское):', days_keyboard($id));
            return;
        }
        if (phone_gate($lead)) {
            $lead = lead_update($id, function ($l) { $l['pending_ok'] = 1; return $l; });
            bot_answer($m, $cbId, 'Сначала поделитесь номером');
            [$t, $b] = phone_ask($lead);
            $show($t, $b);
            return;
        }
        $was = $lead['status'] ?? '';
        $lead = lead_update($id, function ($l) { $l['status'] = 'confirmed'; $l['confirmed_at'] = date('c'); unset($l['reminded'], $l['pending_ok']); lead_event($l, 'confirmed'); return $l; });
        bot_answer($m, $cbId, 'Записал!');
        [$t, $b] = client_card($lead);
        $show($t, $b);
        cards_refresh($id);
        if ($was !== 'confirmed') notify_admins($id, '✅ ' . client_short($lead) . ': время подтверждено – <b>' . h(slot_label($lead['slot_date'], $lead['slot_time'])) . '</b> МСК');
        return;
    }
    if ($act === 'rs') {
        bot_answer($m, $cbId);
        $show('Выберите удобный день (время московское):', days_keyboard($id));
        return;
    }
    if ($act === 'rp' && isset($parts[2]) && ctype_digit($parts[2])) {
        bot_answer($m, $cbId);
        $show('Выберите удобный день (время московское):', days_keyboard($id, (int)$parts[2]));
        return;
    }
    if ($act === 'rd' && isset($parts[2]) && preg_match('/^\d{8}$/', $parts[2])) {
        bot_answer($m, $cbId);
        $kb = slots_keyboard($id, $parts[2]);
        $show(count($kb) > 1 ? '<b>' . h(day_label(ymd_iso($parts[2]))) . '</b> – выберите время (МСК):' : 'На ' . h(day_label(ymd_iso($parts[2]))) . ' свободного времени уже нет – выберите другой день.', $kb);
        return;
    }
    if ($act === 'rt' && isset($parts[2], $parts[3]) && preg_match('/^\d{8}$/', $parts[2]) && preg_match('/^\d{3,4}$/', $parts[3])) {
        $date = substr($parts[2], 0, 4) . '-' . substr($parts[2], 4, 2) . '-' . substr($parts[2], 6, 2);
        $time = substr($parts[3], 0, -2) . ':' . substr($parts[3], -2);
        if (strlen($time) === 4) $time = '0' . $time;
        if (!slot_free($date, $time, null, $id)) {
            bot_answer($m, $cbId, 'Это время уже недоступно');
            $show('Это время уже заняли или оно прошло. Выберите другое (время московское):', days_keyboard($id));
            return;
        }
        $old = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
        if (phone_gate($lead)) {
            $lead = lead_update($id, function ($l) use ($date, $time) {
                $l['slot_date'] = $date; $l['slot_time'] = $time; $l['slot'] = slot_label($date, $time);
                $l['status'] = 'started'; $l['pending_ok'] = 1; unset($l['reminded']);
                lead_event($l, 'slot_chosen', $date . ' ' . $time);
                return $l;
            });
            bot_answer($m, $cbId, 'Сначала поделитесь номером');
            [$t, $b] = phone_ask($lead);
            $show($t, $b);
            cards_refresh($id);
            return;
        }
        $lead = lead_update($id, function ($l) use ($date, $time) {
            $l['slot_date'] = $date; $l['slot_time'] = $time; $l['slot'] = slot_label($date, $time);
            $l['status'] = 'confirmed'; $l['confirmed_at'] = date('c'); unset($l['reminded'], $l['pending_ok']);
            lead_event($l, 'rescheduled', $date . ' ' . $time);
            return $l;
        });
        bot_answer($m, $cbId, 'Записал!');
        [$t, $b] = client_card($lead);
        $show($t, $b);
        cards_refresh($id);
        $new = slot_label($date, $time);
        notify_admins($id, ($old && $old !== $new ? '🔁 ' . client_short($lead) . ': перенос – ' . h($old) . ' → <b>' . h($new) . '</b>' : '✅ ' . client_short($lead) . ': записан на <b>' . h($new) . '</b>') . ' МСК');
        return;
    }
    if ($act === 'cx') {
        $lead = lead_update($id, function ($l) { $l['status'] = 'cancelled'; lead_event($l, 'cancelled'); return $l; });
        bot_answer($m, $cbId, 'Запись отменена');
        $show("Запись отменена. Если захотите вернуться к разбору – нажмите кнопку ниже или просто напишите сюда.", [[['text' => 'Записаться снова', 'data' => 'rs:' . $id]]]);
        cards_refresh($id);
        notify_admins($id, '✖️ ' . client_short($lead) . ': запись отменена');
        return;
    }
    if ($act === 'yes') {
        bot_answer($m, $cbId, 'Отлично, до встречи!');
        lead_update($id, function ($l) { lead_event($l, 'will_come'); return $l; });
        notify_admins($id, '👍 ' . client_short($lead) . ': будет на разборе');
        return;
    }
    bot_answer($m, $cbId);
}

/** Обычное сообщение клиента – пересылаем владельцу. $copy – для Telegram: переслать вложение копией. */
function client_message(string $m, $chat, array $u, string $text, string $mid, bool $hasMedia): void {
    $t = trim($text);
    if (preg_match('/^\/start(?:@\w+)?(?:\s+(\S+))?$/i', $t, $mm)) { client_start($m, $chat, $u, $mm[1] ?? ''); return; }
    if (lead_valid_id($t) && lead_get(strtolower($t))) { client_start($m, $chat, $u, $t); return; }

    $id = chat_lead($m, $chat);
    $lead = $id ? lead_get($id) : null;
    if (!$lead) { $id = lead_new_id(); $lead = bind_client($id, $m, $chat, $u); }
    else if (($lead['client']['chat'] ?? '') !== (string)$chat) { $lead = bind_client($id, $m, $chat, $u); }
    if (!$lead) return;

    $first = empty($lead['msgs']);
    $lead = lead_update($id, function ($l) use ($t) { $l['msgs'] = (int)($l['msgs'] ?? 0) + 1; $l['last_in'] = date('c'); lead_event($l, 'msg_in', $t); return $l; });
    if ($first && empty($lead['cards'])) cards_refresh($id);
    $lead = lead_get($id);

    $head = '💬 <b>' . client_short($lead) . '</b> · #' . h($id) . "\n";
    $body = $t !== '' ? h($t) : '<i>[вложение]</i>';
    $cards = (array)($lead['cards'] ?? []);
    foreach (admins() as $am => $chats) {
        if (($am === 'telegram' && !tg_enabled()) || ($am === 'max' && !max_enabled())) continue;
        foreach ($chats as $achat) {
            if ($am === 'telegram' && $m === 'telegram' && $hasMedia) {
                $hid = bot_send($am, $achat, $head . '<i>вложение ↓</i>', [], $cards[$am . ':' . $achat] ?? null);
                if ($hid) map_put($am, $achat, $hid, $id);
                $c = tg_call('copyMessage', ['chat_id' => $achat, 'from_chat_id' => $chat, 'message_id' => (int)$mid]);
                if (isset($c['message_id'])) map_put($am, $achat, (string)$c['message_id'], $id);
                continue;
            }
            $sid = bot_send($am, $achat, $head . $body . ($hasMedia && $t !== '' ? "\n<i>[+ вложение – откройте чат с клиентом в Max]</i>" : ''), [], $cards[$am . ':' . $achat] ?? null);
            if ($sid) map_put($am, $achat, $sid, $id);
        }
    }

    /* Автоответ – не чаще раза в 6 часов */
    $last = isset($lead['ack_at']) ? strtotime((string)$lead['ack_at']) : 0;
    if (time() - (int)$last > 6 * 3600) {
        lead_update($id, function ($l) { $l['ack_at'] = date('c'); return $l; });
        $btn = slot_future($lead) && ($lead['status'] ?? '') !== 'cancelled' ? [] : [[['text' => 'Записаться на онлайн-разбор', 'data' => 'rs:' . $id]]];
        bot_send($m, $chat, 'Спасибо, передал ' . h(owner_dative()) . ' – он ответит прямо здесь, обычно в течение пары часов в рабочее время.', $btn);
    }
}

/* ===================== Сценарий владельца ===================== */
/** Возвращает true, если сообщение обработано как команда/ответ администратора. */
function admin_message(string $m, $chat, array $u, string $text, string $mid, ?string $replyTo, bool $hasMedia): bool {
    $t = trim($text);

    if (preg_match('/^\/admin(?:@\w+)?\s+(\S+)$/i', $t, $mm)) {
        $code = (string)cfg('admin_code', '');
        if ($code !== '' && hash_equals($code, $mm[1])) {
            admin_add($m, $chat, user_name($u));
            if ($m === 'telegram') {
                tg_call('setMyCommands', ['scope' => ['type' => 'chat', 'chat_id' => $chat], 'commands' => [
                    ['command' => 'leads', 'description' => 'Ближайшие записи и заявки'],
                    ['command' => 'clean', 'description' => 'Удалить отменённые и прошедшие'],
                    ['command' => 'help', 'description' => 'Как пользоваться'],
                ]]);
            }
            bot_send($m, $chat, "Готово, теперь вы администратор. Сюда будут приходить заявки.\n\n" . admin_help());
        } else {
            bot_send($m, $chat, 'Неверный код.');
        }
        return true;
    }
    if (!is_admin($m, $chat)) return false;

    if ($replyTo !== null) {
        $id = map_get($m, $chat, $replyTo);
        if ($id !== null) {
            if (preg_match('/^(?:\/move|перенос|перенеси|перенести)\b/iu', $t)) { admin_move_text($m, $chat, $id, $t, $mid); return true; }
            if (preg_match('/^(?:\/cancel|отмена|отменить|отмени)$/iu', $t)) { admin_cancel_ask($m, $chat, $id, null); return true; }
            admin_reply($m, $chat, $id, $t, $mid, $hasMedia);
            return true;
        }
    }
    if (preg_match('/^\/(help|start)(?:@\w+)?$/i', $t)) { bot_send($m, $chat, admin_help()); return true; }
    if (preg_match('/^\/leads(?:@\w+)?$/i', $t)) { [$ltxt, $lkb] = admin_list(); bot_send($m, $chat, $ltxt, $lkb); return true; }
    if (preg_match('/^\/(?:del|delete|удалить)(?:@\w+)?[_\s]+([a-z0-9]{6,24})$/iu', $t, $mm)) {
        if (!lead_get(strtolower($mm[1]))) { bot_send($m, $chat, 'Заявка не найдена.'); return true; }
        admin_delete_ask($m, $chat, strtolower($mm[1]), null);
        return true;
    }
    if (preg_match('/^\/clean(?:@\w+)?$/i', $t)) {
        $list = leads_cleanable();
        $total = count(glob(data_dir() . '/leads/*.json') ?: []);
        if (!$total) { bot_send($m, $chat, 'Заявок нет – чистить нечего.'); return true; }
        $c = ['cancelled' => 0, 'past' => 0, 'old' => 0];
        foreach ($list as $l) { if (($l['status'] ?? '') === 'cancelled') $c['cancelled']++; elseif (slot_ts($l) !== null) $c['past']++; else $c['old']++; }
        $kb = [];
        if ($list) $kb[] = [['text' => '🧹 Отменённые и прошедшие (' . count($list) . ')', 'data' => 'ac:all']];
        $kb[] = [['text' => '🗑 Вообще все заявки (' . $total . ')', 'data' => 'ac:every']];
        $kb[] = [['text' => 'Ничего не удалять', 'data' => 'ac:no']];
        bot_send($m, $chat, '🧹 <b>Что удалить?</b>' . "
• отменённые: " . $c['cancelled'] . "
• прошедшие (разбор был больше суток назад): " . $c['past'] . "
• без записи, старше 14 дней: " . $c['old'] . "
• всего заявок: " . $total . "

Карточки и переписка исчезнут из чата, данные удалятся с сервера. Одну заявку можно удалить кнопкой «🗑» на карточке или в /leads.", $kb);
        return true;
    }
    if (preg_match('/^\/lead[_\s]([a-z0-9]{6,24})(?:@\w+)?$/i', $t, $mm)) {
        $lead = lead_get(strtolower($mm[1]));
        if (!$lead) { bot_send($m, $chat, 'Заявка не найдена.'); return true; }
        [$txt, $btn] = admin_card($lead);
        $sid = bot_send($m, $chat, $txt, $btn);
        if ($sid) map_put($m, $chat, $sid, $lead['id']);
        return true;
    }
    if (str_starts_with($t, '/start ')) return false; /* владелец проверяет запись как клиент */
    bot_send($m, $chat, 'Чтобы написать клиенту, ответьте на карточку заявки или на его сообщение.' . "\n\n/leads – последние заявки");
    return true;
}

function admin_help(): string {
    return "<b>Как это работает</b>\n"
        . "• Каждая заявка с сайта приходит сюда карточкой: имя, ник со ссылкой на профиль, ответы квиза. Статус в карточке обновляется сам.\n"
        . "• Чтобы написать клиенту (и прислать ссылку на созвон), ответьте на карточку или на его сообщение – текст уйдёт ему в Telegram или Max от имени бота.\n"
        . "• <b>Перенос:</b> кнопка «🔁 Перенести» на карточке – выберите день и время. Или ответьте на карточку: <code>перенос 28.10 15:30</code> – подойдёт любое время, не только из сетки. Клиенту ничего делать не нужно: бот сам сообщит новое время и пересчитает напоминания.\n"
        . "• <b>Отмена:</b> кнопка «✖️ Отменить» или ответ «отмена».\n"
        . "• Напоминания клиенту бот шлёт сам: накануне, за час, за 15 минут и в момент начала. Вам – за час, за 15 минут и в момент начала, чтобы не забыть про ссылку.\n\n"
        . "• <b>Удаление:</b> любую заявку, в том числе будущую и подтверждённую, можно удалить кнопкой «🗑» в /leads или на карточке – заявка удалится с сервера, а её карточка и переписка исчезнут из этого чата.\n\n"
        . "/leads – ближайшие записи и последние заявки, у каждой кнопка «🗑»\n/clean – массовая очистка: отменённые и прошедшие или вообще все\n/del_&lt;id&gt; – удалить одну заявку по номеру\n/lead_&lt;id&gt; – прислать карточку заново";
}

function admin_list(): array {
    $all = leads_recent(0, 120);
    if (!$all) return ['Заявок пока нет.', []];
    $line = function (array $l): string {
        $when = slot_label($l['slot_date'] ?? null, $l['slot_time'] ?? null);
        $c = $l['client'] ?? [];
        [$nick] = $c ? client_profile($c) : [''];
        return status_mark((string)($l['status'] ?? 'new')) . ' ' . ($when ? h($when) : 'без времени') . ' · '
            . h(trim((string)($c['name'] ?? '')) ?: 'не открыл бота') . ($nick !== '' ? ' ' . h($nick) : '') . ($c ? ' · ' . messenger_name((string)$c['m']) : '')
            . "\n/lead_" . h($l['id']) . "\n";
    };
    $up = array_values(array_filter($all, function ($l) { return ($l['status'] ?? '') === 'confirmed' && slot_future($l, -60); }));
    usort($up, function ($x, $y) { return slot_ts($x) <=> slot_ts($y); });
    $out = '';
    if ($up) { $out .= "<b>Ближайшие созвоны</b>\n"; foreach (array_slice($up, 0, 15) as $l) $out .= "\n" . $line($l); }
    $upIds = array_column($up, 'id');
    $rest = array_slice(array_values(array_filter($all, function ($l) use ($upIds) { return !in_array($l['id'], $upIds, true); })), 0, 10);
    if ($rest) { $out .= ($out ? "\n" : '') . "<b>Остальные заявки</b>\n"; foreach ($rest as $l) $out .= "\n" . $line($l); }
    $kb = [];
    foreach (array_merge(array_slice($up, 0, 15), $rest) as $l) {
        $when = slot_label($l['slot_date'] ?? null, $l['slot_time'] ?? null);
        $c = $l['client'] ?? [];
        $nm = trim((string)($c['name'] ?? '')) ?: 'без имени';
        $kb[] = [['text' => '🗑 ' . mb_substr($nm, 0, 18) . ($when ? ' · ' . $when : ''), 'data' => 'ak:' . $l['id']]];
    }
    return [$out . "\nКнопки «🗑» ниже удаляют любую заявку.\n/clean – массовая очистка", $kb];
}

function admin_reply(string $m, $chat, string $id, string $text, string $mid, bool $hasMedia): void {
    $lead = lead_get($id);
    $c = $lead['client'] ?? null;
    if (!$lead || !$c) { bot_send($m, $chat, 'Клиент ещё не открыл бота – написать ему пока нельзя.', [], $mid); return; }
    $ok = false;
    if ($c['m'] === 'telegram' && $m === 'telegram' && $hasMedia) {
        $ok = tg_call('copyMessage', ['chat_id' => $c['chat'], 'from_chat_id' => $chat, 'message_id' => (int)$mid]) !== null;
    } else if ($text !== '') {
        $ok = bot_send((string)$c['m'], $c['chat'], h($text)) !== null;
    } else {
        bot_send($m, $chat, 'В ' . messenger_name((string)$c['m']) . ' из этого чата можно отправить только текст.', [], $mid);
        return;
    }
    if ($ok) {
        lead_update($id, function ($l) use ($text) { $l['last_out'] = date('c'); lead_event($l, 'msg_out', $text); return $l; });
        if ($m === 'telegram') {
            if (tg_call('setMessageReaction', ['chat_id' => $chat, 'message_id' => (int)$mid, 'reaction' => [['type' => 'emoji', 'emoji' => '👌']]]) === null) bot_send($m, $chat, '✓ Отправлено', [], $mid);
        } else {
            bot_send($m, $chat, '✓ Отправлено', [], $mid);
        }
    } else {
        bot_send($m, $chat, 'Не получилось отправить: возможно, клиент остановил бота.', [], $mid);
    }
}

/* ---------- Удаление заявок владельцем ---------- */
/** Удалить заявку полностью: файл, привязку чата, строку журнала и все сообщения по ней в чатах администраторов. */
function lead_delete(string $id): void {
    $id = strtolower($id);
    if (!lead_valid_id($id)) return;
    $lead = lead_get($id);
    /* Сообщения в чатах администраторов: карточки, уведомления, пересланные сообщения клиента */
    $mids = with_lock('replymap', function () use ($id) {
        $f = data_dir() . '/replymap.json';
        $d = json_read($f, []);
        $out = [];
        foreach ($d as $k => $v) if ((string)$v === $id) { $out[] = $k; unset($d[$k]); }
        json_write($f, $d);
        return $out;
    }) ?: [];
    foreach ((array)($lead['cards'] ?? []) as $key => $mid) $mids[] = $key . ':' . $mid;
    foreach (array_unique($mids) as $k) {
        $parts = explode(':', (string)$k, 3);
        if (count($parts) === 3) bot_delete($parts[0], $parts[1], $parts[2]);
    }
    if ($lead && !empty($lead['client'])) {
        $cf = chat_file((string)$lead['client']['m'], $lead['client']['chat']);
        if (chat_lead((string)$lead['client']['m'], $lead['client']['chat']) === $id) @unlink($cf);
    }
    @unlink(lead_file($id));
    with_lock('journal', function () use ($id) {
        $f = data_dir() . '/leads.jsonl';
        if (!is_file($f)) return true;
        $keep = [];
        foreach ((array)@file($f, FILE_IGNORE_NEW_LINES) as $line) {
            $j = json_decode((string)$line, true);
            if (!is_array($j) || strtolower((string)($j['id'] ?? '')) !== $id) $keep[] = $line;
        }
        @file_put_contents($f, $keep ? implode("\n", $keep) . "\n" : '');
        return true;
    });
}

/** Что можно убрать командой /clean: отменённые, прошедшие (разбор был больше суток назад) и брошенные без времени старше 14 дней. */
function leads_cleanable(): array {
    $out = [];
    $now = time();
    foreach (glob(data_dir() . '/leads/*.json') ?: [] as $f) {
        $l = json_read($f, null);
        if (!is_array($l) || empty($l['id'])) continue;
        $ts = slot_ts($l);
        $age = $now - (strtotime((string)($l['updated'] ?? $l['created'] ?? '')) ?: $now);
        if (($l['status'] ?? '') === 'cancelled' || ($ts !== null && $ts < $now - 86400) || ($ts === null && $age > 14 * 86400)) $out[] = $l;
    }
    return $out;
}

/** Спросить подтверждение и удалить любую заявку: новую, подтверждённую, будущую, отменённую. */
function admin_delete_ask(string $m, $chat, string $id, ?string $replyTo): void {
    $lead = lead_get($id);
    if (!$lead) return;
    $sid = bot_send($m, $chat, '🗑 Удалить заявку ' . client_short($lead) . ' (#' . h($id) . ')?' . "
Карточка и вся переписка по ней исчезнут из этого чата, данные удалятся с сервера. Вернуть будет нельзя." . (slot_future($lead, 0) && ($lead['status'] ?? '') === 'confirmed' ? "

⚠️ Запись ещё впереди – клиенту не придёт ни отмена, ни напоминания." : ''),
        [[['text' => 'Да, удалить', 'data' => 'az:' . $id], ['text' => 'Нет', 'data' => 'an:' . $id]]], $replyTo);
    if ($sid) map_put($m, $chat, $sid, $id);
}

/* ---------- Перенос и отмена записи владельцем ---------- */
function admin_callback(string $m, $chat, string $data, string $cbId, ?string $mid): void {
    $p = explode(':', $data);
    $act = $p[0];
    $id = strtolower($p[1] ?? '');
    if ($act === 'ac') {
        if ($id === 'no') { bot_answer($m, $cbId, 'Ничего не удалял'); if ($mid !== null) bot_delete($m, $chat, $mid); return; }
        if ($id === 'every') {
            bot_answer($m, $cbId);
            $total = count(glob(data_dir() . '/leads/*.json') ?: []);
            bot_send($m, $chat, '⚠️ <b>Удалить ВСЕ заявки (' . $total . ')?</b>' . "
Включая подтверждённые будущие созвоны: клиентам не придут ни отмены, ни напоминания. Вернуть будет нельзя.",
                [[['text' => 'Да, удалить всё', 'data' => 'ac:every2'], ['text' => 'Нет', 'data' => 'ac:no']]]);
            return;
        }
        $n = 0;
        $all = $id === 'every2';
        $victims = $all ? array_map(function ($f) { return json_read($f, null); }, glob(data_dir() . '/leads/*.json') ?: []) : leads_cleanable();
        foreach ($victims as $l) { if (is_array($l) && !empty($l['id'])) { lead_delete((string)$l['id']); $n++; } }
        bot_answer($m, $cbId, 'Удалено: ' . $n);
        if ($mid !== null) bot_delete($m, $chat, $mid);
        return;
    }
    $lead = lead_get($id);
    if (!$lead) { bot_answer($m, $cbId, 'Заявка уже удалена'); if ($mid !== null) bot_delete($m, $chat, $mid); return; }
    if ($act === 'ak') { bot_answer($m, $cbId); admin_delete_ask($m, $chat, $id, $mid); return; }
    if ($act === 'az') { lead_delete($id); bot_answer($m, $cbId, 'Заявка удалена'); return; }
    $who = client_short($lead);
    $now = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    $head = '🔁 <b>Перенос записи</b> · ' . $who . "\n" . ($now && ($lead['status'] ?? '') !== 'cancelled' ? 'Сейчас: ' . h($now) . ' МСК' : 'Время не назначено') . "\n\n";
    $tip = "\n\n<i>Нужно время не из сетки – ответьте на карточку: перенос 28.10 15:30</i>";
    /* Первое нажатие на карточке – присылаем выбор отдельным сообщением, сама карточка остаётся */
    $show = function (string $text, array $btn, bool $fresh = false) use ($m, $chat, $mid, $id) {
        if (!$fresh && $mid !== null && bot_edit($m, $chat, $mid, $text, $btn)) return;
        $sid = bot_send($m, $chat, $text, $btn, $fresh ? $mid : null);
        if ($sid) map_put($m, $chat, $sid, $id);
    };
    if ($act === 'ar') { bot_answer($m, $cbId); $show($head . 'Выберите новый день:' . $tip, days_keyboard($id, 0, true), !is_flow_msg($m, $chat, $mid)); return; }
    if ($act === 'ap' && isset($p[2]) && ctype_digit($p[2])) { bot_answer($m, $cbId); $show($head . 'Выберите новый день:' . $tip, days_keyboard($id, (int)$p[2], true)); return; }
    if ($act === 'ad' && isset($p[2]) && preg_match('/^\d{8}$/', $p[2])) {
        bot_answer($m, $cbId);
        $show($head . '<b>' . h(day_label(ymd_iso($p[2]))) . '</b> – выберите время (МСК):' . $tip, slots_keyboard($id, $p[2], true));
        return;
    }
    if ($act === 'at' && isset($p[2], $p[3]) && preg_match('/^\d{8}$/', $p[2]) && preg_match('/^\d{3,4}$/', $p[3])) {
        $time = str_pad(substr($p[3], 0, -2), 2, '0', STR_PAD_LEFT) . ':' . substr($p[3], -2);
        $res = admin_move($id, ymd_iso($p[2]), $time);
        bot_answer($m, $cbId, $res['ok'] ? 'Перенёс' : 'Не получилось');
        $show($res['text'], []);
        return;
    }
    if ($act === 'ax') { bot_answer($m, $cbId); admin_cancel_ask($m, $chat, $id, $mid); return; }
    if ($act === 'ay') {
        $res = admin_cancel($id);
        bot_answer($m, $cbId, 'Отменено');
        $show($res, []);
        return;
    }
    if ($act === 'an') { bot_answer($m, $cbId, 'Оставил как есть'); if ($mid !== null && !bot_delete($m, $chat, $mid)) $show('Оставил как есть: ' . $who . ($now ? ', ' . h($now) . ' МСК' : ''), []); return; }
    bot_answer($m, $cbId);
}

/* Сообщение с выбором дня/времени можно редактировать, а карточку заявки – нет: выбор присылаем отдельным сообщением */
function is_flow_msg(string $m, $chat, ?string $mid): bool {
    if ($mid === null) return false;
    $lead = null;
    $id = map_get($m, $chat, $mid);
    if ($id) $lead = lead_get($id);
    /* Карточка заявки – не редактируем, присылаем выбор отдельным сообщением */
    if ($lead && (string)($lead['cards'][$m . ':' . $chat] ?? '') === (string)$mid) return false;
    return true;
}

function admin_cancel_ask(string $m, $chat, string $id, ?string $mid): void {
    $lead = lead_get($id);
    if (!$lead) return;
    $when = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    $text = 'Отменить запись ' . client_short($lead) . ($when ? ' на ' . h($when) . ' МСК' : '') . '? Клиенту придёт сообщение об отмене.';
    $btn = [[['text' => 'Да, отменить', 'data' => 'ay:' . $id], ['text' => 'Нет', 'data' => 'an:' . $id]]];
    $sid = bot_send($m, $chat, $text, $btn, $mid);
    if ($sid) map_put($m, $chat, $sid, $id);
}

function admin_cancel(string $id): string {
    $lead = lead_get($id);
    if (!$lead) return 'Заявка не найдена.';
    $when = slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null);
    $lead = lead_update($id, function ($l) { $l['status'] = 'cancelled'; lead_event($l, 'cancelled_by_admin'); return $l; });
    $c = $lead['client'] ?? null;
    $sent = false;
    if ($c) {
        $sent = bot_send((string)$c['m'], $c['chat'], 'Онлайн-разбор' . ($when ? ' на <b>' . h($when) . '</b> МСК' : '') . ' отменён. Если захотите записаться снова – нажмите кнопку ниже или просто напишите сюда.', [[['text' => 'Записаться снова', 'data' => 'rs:' . $id]]]) !== null;
    }
    cards_refresh($id);
    return '✖️ Запись ' . client_short($lead) . ' отменена.' . ($c ? ($sent ? ' Клиенту отправлено сообщение.' : ' Сообщение клиенту не дошло – возможно, он остановил бота.') : '');
}

/** Перенести запись на любое время (владелец). Клиенту приходит новое время, напоминания пересчитываются. */
function admin_move(string $id, string $date, string $time): array {
    $ts = strtotime($date . ' ' . $time . ':00');
    if (!$ts) return ['ok' => false, 'text' => 'Не понял дату или время.'];
    if ($ts < time() + 5 * 60) return ['ok' => false, 'text' => 'Это время уже прошло – выберите другое.'];
    $lead = lead_get($id);
    if (!$lead) return ['ok' => false, 'text' => 'Заявка не найдена.'];
    $old = ($lead['status'] ?? '') !== 'cancelled' ? slot_label($lead['slot_date'] ?? null, $lead['slot_time'] ?? null) : '';
    $new = slot_label($date, $time);
    $clash = booking_busy($id)[$date . ' ' . $time] ?? null;
    $lead = lead_update($id, function ($l) use ($date, $time) {
        $l['slot_date'] = $date; $l['slot_time'] = $time; $l['slot'] = slot_label($date, $time);
        $l['status'] = 'confirmed'; $l['confirmed_at'] = date('c'); unset($l['reminded']);
        lead_event($l, 'moved_by_admin', $date . ' ' . $time);
        return $l;
    });
    $c = $lead['client'] ?? null;
    $sent = false;
    if ($c) {
        $txt = '🗓 <b>Время онлайн-разбора изменилось</b>' . "\n"
            . ($old && $old !== $new ? 'Было: <s>' . h($old) . '</s>' . "\n" : '')
            . 'Теперь: <b>' . h($new) . '</b> по Москве' . "\n\n"
            . 'Ничего подтверждать не нужно – я уже всё записал. Напомню накануне, за час и за 15 минут до начала, ссылку на созвон пришлю сюда.';
        $sent = bot_send((string)$c['m'], $c['chat'], $txt, [[['text' => 'Не подходит – выбрать другое', 'data' => 'rs:' . $id]]]) !== null;
    }
    cards_refresh($id);
    $out = '✅ Перенёс ' . client_short($lead) . ($old && $old !== $new ? ': ' . h($old) . ' → ' : ' на ') . '<b>' . h($new) . '</b> МСК.';
    $out .= $c ? ($sent ? ' Клиенту отправлено новое время, напоминания пересчитаны.' : ' Сообщение клиенту не дошло – возможно, он остановил бота.') : ' Клиент ещё не открыл бота – увидит время, когда откроет.';
    if ($clash) $out .= "\n⚠️ На это время уже есть другая запись: /lead_" . h($clash);
    return ['ok' => true, 'text' => $out];
}

/** «перенос 28.10 15:30», «перенос 28.10.2026 в 9:00», «/move 28.10 15:30» – ответом на карточку */
function admin_move_text(string $m, $chat, string $id, string $t, string $mid): void {
    if (!preg_match('/(\d{1,2})[.\/-](\d{1,2})(?:[.\/-](\d{2,4}))?\D+(\d{1,2})[:.](\d{2})/u', $t, $x)) {
        bot_send($m, $chat, 'Напишите дату и время так: <code>перенос 28.10 15:30</code> (время московское).', [], $mid);
        return;
    }
    [, $d, $mo, $y, $hh, $mi] = $x;
    $d = (int)$d; $mo = (int)$mo; $hh = (int)$hh; $mi = (int)$mi;
    if ($y === '') { $y = (int)date('Y'); if (strtotime(sprintf('%04d-%02d-%02d 23:59', $y, $mo, $d)) < time()) $y++; }
    else { $y = (int)$y; if ($y < 100) $y += 2000; }
    if (!checkdate($mo, $d, $y) || $hh > 23 || $mi > 59) { bot_send($m, $chat, 'Такой даты или времени нет – проверьте, пожалуйста.', [], $mid); return; }
    $res = admin_move($id, sprintf('%04d-%02d-%02d', $y, $mo, $d), sprintf('%02d:%02d', $hh, $mi));
    $sid = bot_send($m, $chat, $res['text'], [], $mid);
    if ($sid) map_put($m, $chat, $sid, $id);
}

/* ===================== Напоминания (cron) ===================== */
function run_reminders(): array {
    $done = [];
    $r = with_lock('cron', function () use (&$done) {
        $now = time();
        foreach (leads_recent(0, 60) as $l) {
            $id = $l['id'];
            $c = $l['client'] ?? null;
            if (!$c) continue;
            $st = $l['status'] ?? '';
            $ts = slot_ts($l);
            $rem = (array)($l['reminded'] ?? []);
            $when = slot_label($l['slot_date'] ?? null, $l['slot_time'] ?? null);

            if ($st === 'confirmed' && $ts !== null) {
                $stage = reminder_stage($l, $now);
                if ($stage !== null && empty($rem[$stage]) && reminder_send($l, $stage, $now)) {
                    /* Отмечаем и этот этап, и все предыдущие – чтобы после паузы крона не прислать устаревшее «завтра» */
                    foreach (['day', '1h', '15m', 'now'] as $s) { if (empty($rem[$s])) $rem[$s] = date('c'); if ($s === $stage) break; }
                    $done[] = $id . ' ' . $stage;
                }
            }
            if ($st === 'started' && empty($rem['nudge']) && $ts !== null && $ts > $now + 3 * 3600) {
                $started = strtotime((string)($l['started_at'] ?? 'now')) ?: $now;
                if ($now - $started > 2 * 3600) {
                    [$t, $b] = client_card($l);
                    bot_send((string)$c['m'], $c['chat'], 'Вы ещё не подтвердили время разбора – ' . h($when) . ' МСК. Подходит?' . "\n\n" . $t, $b);
                    $rem['nudge'] = date('c'); $done[] = $id . ' nudge';
                }
            }
            if ($rem !== (array)($l['reminded'] ?? [])) {
                lead_update($id, function ($x) use ($rem) { if (!$x) return null; $x['reminded'] = $rem; return $x; });
            }
        }
        json_write(data_dir() . '/cron.json', ['last' => time()]);
        return true;
    }, false);
    return $r === null ? ['skipped: already running'] : $done;
}

/**
 * Какое напоминание актуально сейчас: day – накануне, 1h – за час, 15m – за 15 минут, now – в момент начала.
 * «Накануне» шлём, только если запись сделана заранее (больше 8 часов до разбора), и не раньше чем через 3 часа после записи.
 */
function reminder_stage(array $l, int $now): ?string {
    $ts = slot_ts($l);
    if ($ts === null) return null;
    $conf = strtotime((string)($l['confirmed_at'] ?? '')) ?: 0;
    if ($now - $conf < 5 * 60) return null; /* только что записался – подтверждение уже получил */
    if ($now >= $ts - 60 && $now < $ts + 20 * 60) return 'now';
    if ($now >= $ts - 15 * 60 && $now < $ts - 2 * 60) return '15m';
    if ($now >= $ts - 60 * 60 && $now < $ts - 20 * 60) return '1h';
    if ($ts - $conf > 8 * 3600 && $now >= max($ts - 86400, $conf + 3 * 3600) && $now < $ts - 3 * 3600) return 'day';
    return null;
}

function minutes_word(int $n): string {
    $n = max(1, $n);
    $w = ($n % 10 === 1 && $n % 100 !== 11) ? 'минуту' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'минуты' : 'минут');
    return $n . ' ' . $w;
}

function reminder_send(array $l, string $stage, int $now): bool {
    $id = $l['id'];
    $c = $l['client'];
    $ts = (int)slot_ts($l);
    $time = h((string)$l['slot_time']);
    $left = (int)round(($ts - $now) / 60);
    /* Ссылку на созвон владелец присылает сам – если уже писал клиенту за последние 6 часов, ссылка, скорее всего, выше */
    $wrote = !empty($l['last_out']) && (strtotime((string)$l['last_out']) ?: 0) > $ts - 6 * 3600;
    $linkLine = $wrote ? 'Ссылка на созвон – выше в этом чате.' : 'Ссылку на созвон пришлю сюда.';
    $mc = (string)$c['m'];
    if ($stage === 'day') {
        $day = date('Y-m-d', $ts) === date('Y-m-d', $now + 86400) ? 'завтра' : (date('Y-m-d', $ts) === date('Y-m-d', $now) ? 'сегодня' : h(day_label((string)$l['slot_date'])));
        return bot_send($mc, $c['chat'], '📅 Напоминаю: <b>' . $day . ' в ' . $time . '</b> по Москве у нас онлайн-разбор вашей рекламы – 30 минут по видеосвязи. Всё в силе?', [
            [['text' => '👍 Буду', 'data' => 'yes:' . $id], ['text' => 'Перенести', 'data' => 'rs:' . $id]],
        ] + (($or = owner_row($l)) ? [1 => $or[0]] : [])) !== null;
    }
    if ($stage === '1h') {
        $ok = bot_send($mc, $c['chat'], '⏰ ' . ($left >= 55 ? 'Через час' : 'Через ' . minutes_word($left)) . ', в <b>' . $time . '</b> МСК, онлайн-разбор вашей рекламы. ' . $linkLine, array_merge([[['text' => 'Не успеваю – перенести', 'data' => 'rs:' . $id]]], owner_row($l))) !== null;
        notify_admins($id, '⏰ ' . ($left >= 55 ? 'Через час' : 'Через ' . minutes_word($left)) . ' разбор: ' . client_short($l) . ', ' . $time . ' МСК.' . ($wrote ? '' : "\nОтветьте на это сообщение ссылкой на созвон – она уйдёт клиенту."));
        return $ok;
    }
    if ($stage === '15m') {
        $ok = bot_send($mc, $c['chat'], '⏳ Через ' . minutes_word($left) . ', в <b>' . $time . '</b> МСК, начинаем онлайн-разбор. ' . $linkLine) !== null;
        notify_admins($id, '⏳ Через ' . minutes_word($left) . ' разбор: ' . client_short($l) . ', ' . $time . ' МСК.');
        return $ok;
    }
    $ok = bot_send($mc, $c['chat'], '🔔 <b>Начинаем!</b> Онлайн-разбор вашей рекламы – сейчас, в ' . $time . ' МСК. ' . ($wrote ? 'Ссылка на созвон – выше в этом чате.' : 'Ссылка на созвон придёт сюда в течение пары минут.')) !== null;
    notify_admins($id, '🔔 Время созвона: ' . client_short($l) . ', ' . $time . ' МСК.' . ($wrote ? '' : "\nЕсли ещё не отправили ссылку – ответьте на это сообщение."));
    return $ok;
}

/** «Крон для бедных»: если планировщик на хостинге не настроен, напоминания проверяются при каждом обращении к боту. */
function maybe_run_reminders(): void {
    $c = json_read(data_dir() . '/cron.json', []);
    if (time() - (int)($c['last'] ?? 0) < 55) return;
    run_reminders();
}

/** Ответить вебхуку 200 сразу, а работу продолжить (если сервер умеет). */
function finish_request(): void {
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); return; }
    if (function_exists('litespeed_finish_request')) { litespeed_finish_request(); return; }
}

/* ---------- Обработка одного обновления Telegram (вебхук и опрос) ---------- */
function tg_user(array $from): array {
    return [
        'id' => (string)($from['id'] ?? ''),
        'name' => trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')),
        'username' => (string)($from['username'] ?? ''),
        'first' => (string)($from['first_name'] ?? ''),
    ];
}

function tg_process_update(array $u): void {
    try {
        if (isset($u['message'])) {
            $msg = $u['message'];
            $chat = $msg['chat'] ?? [];
            /* Работаем только в личных чатах */
            if (($chat['type'] ?? '') === 'private') {
                $user = tg_user($msg['from'] ?? []);
                $text = (string)($msg['text'] ?? ($msg['caption'] ?? ''));
                $hasMedia = !isset($msg['text']);
                $mid = (string)($msg['message_id'] ?? '');
                $replyTo = isset($msg['reply_to_message']['message_id']) ? (string)$msg['reply_to_message']['message_id'] : null;
                if (!admin_message('telegram', $chat['id'], $user, $text, $mid, $replyTo, $hasMedia)) {
                    client_message('telegram', $chat['id'], $user, $text, $mid, $hasMedia);
                }
            }
        } elseif (isset($u['callback_query'])) {
            $cb = $u['callback_query'];
            $chatId = $cb['message']['chat']['id'] ?? null;
            if ($chatId !== null) {
                client_callback('telegram', $chatId, tg_user($cb['from'] ?? []), (string)($cb['data'] ?? ''), (string)$cb['id'], isset($cb['message']['message_id']) ? (string)$cb['message']['message_id'] : null);
            } else {
                bot_answer('telegram', (string)$cb['id']);
            }
        }
    } catch (Throwable $e) {
        log_line('TG update error: ' . $e->getMessage() . ' @' . $e->getLine());
    }
}
