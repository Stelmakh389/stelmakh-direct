<?php
/*
 * Приём заявок с лендинга.
 * 1) Сохраняет заявку в data/leads/<id>.json на вашем хостинге (первичное хранение – в РФ).
 * 2) Присылает карточку заявки вам в бота (Telegram и/или Max) – после того как вы стали администратором (/admin <код>).
 * Подтверждение времени, переписка и напоминания – в tg.php / max.php / cron.php.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(405, ['ok' => false, 'error' => 'method']);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = (array)cfg('allowed_origins', []);
if ($origin !== '' && $allowed && !in_array($origin, $allowed, true)) respond(403, ['ok' => false, 'error' => 'origin']);

$raw = file_get_contents('php://input', false, null, 0, 32768);
$data = json_decode($raw ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'json']);

$sources = ['quiz_booking' => 'quiz', 'booking' => 'form'];
$type = (string)($data['type'] ?? '');
if (!isset($sources[$type])) respond(400, ['ok' => false, 'error' => 'type']);
$messenger = (string)($data['messenger'] ?? '');
if (!in_array($messenger, ['telegram', 'max'], true)) respond(400, ['ok' => false, 'error' => 'messenger']);
if (empty($data['consent'])) respond(422, ['ok' => false, 'error' => 'consent']);
$id = strtolower((string)($data['leadId'] ?? ''));
if (!lead_valid_id($id)) respond(400, ['ok' => false, 'error' => 'id']);

/* ---------- Ограничение частоты ---------- */
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ipHash = hash('sha256', $ip . '|' . cfg('webhook_secret', ''));
$limit = (int)cfg('rate_limit', 10);
$over = with_lock('rate', function () use ($ipHash, $limit) {
    $f = data_dir() . '/rate.json';
    $d = json_read($f, []);
    $now = time();
    foreach ($d as $k => $hits) {
        $d[$k] = array_values(array_filter((array)$hits, function ($t) use ($now) { return $t > $now - 600; }));
        if (!$d[$k]) unset($d[$k]);
    }
    $mine = $d[$ipHash] ?? [];
    if (count($mine) >= $limit) return true;
    $mine[] = $now;
    $d[$ipHash] = $mine;
    json_write($f, $d);
    return false;
});
if ($over) respond(429, ['ok' => false, 'error' => 'rate']);

/* ---------- Очистка полей ---------- */
function clean($v, int $max = 300): string {
    $s = trim(strip_tags((string)$v));
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    return u_sub($s, 0, $max);
}

$quiz = is_array($data['quiz'] ?? null) ? $data['quiz'] : [];
$answers = [];
foreach ((array)($quiz['answers'] ?? []) as $a) {
    if (is_array($a)) $answers[] = ['q' => clean($a['q'] ?? '', 120), 'a' => clean($a['a'] ?? '', 120)];
}
$forecast = [];
foreach (['cpc', 'clicks', 'leads', 'cpl', 'verdict'] as $k) {
    if (!empty($quiz['forecast'][$k])) $forecast[$k] = clean($quiz['forecast'][$k], 160);
}
$marks = [];
foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'yclid'] as $k) {
    if (!empty($data['marks'][$k])) $marks[$k] = clean($data['marks'][$k], 200);
}
$slotDate = isset($data['slot']['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data['slot']['date']) ? (string)$data['slot']['date'] : '';
$slotTime = isset($data['slot']['time']) && preg_match('/^\d{1,2}:\d{2}$/', (string)$data['slot']['time']) ? (string)$data['slot']['time'] : '';
if (strlen($slotTime) === 4) $slotTime = '0' . $slotTime;

$prev = lead_get($id);
$lead = lead_update($id, function ($l) use ($type, $sources, $messenger, $data, $answers, $forecast, $marks, $slotDate, $slotTime, $quiz, $ipHash) {
    $isNew = !$l;
    if ($isNew) $l = ['id' => '', 'created' => date('c'), 'status' => 'new'];
    $l['source'] = $sources[$type];
    $l['messenger'] = $messenger;
    if (($site = clean($data['site'] ?? '', 200)) !== '') $l['site'] = $site;
    if ($slotDate !== '' && $slotTime !== '') {
        $changed = ($l['slot_date'] ?? '') !== $slotDate || ($l['slot_time'] ?? '') !== $slotTime;
        $l['slot_date'] = $slotDate;
        $l['slot_time'] = $slotTime;
        $l['slot'] = slot_label($slotDate, $slotTime);
        /* Новое время с сайта – клиент должен подтвердить его заново */
        if ($changed && ($l['status'] ?? '') !== 'new') { $l['status'] = 'started'; $l['started_at'] = date('c'); unset($l['reminded']); }
    }
    if ($answers) { $l['answers'] = $answers; $l['segment'] = clean($quiz['segment'] ?? '', 20); }
    if ($forecast) $l['forecast'] = $forecast;
    if ($marks) $l['marks'] = $marks;
    $l['page'] = clean($data['page'] ?? '', 300);
    $l['referrer'] = clean($data['referrer'] ?? '', 300);
    $l['consent'] = date('c');
    $l['ip_hash'] = $ipHash;
    lead_event($l, $isNew ? 'site_' . $sources[$type] : 'site_update', $l['slot'] ?? '');
    return $l;
});
if (!$lead) respond(500, ['ok' => false, 'error' => 'write']);

/* Журнал всех заявок одной строкой – удобно выгрузить в таблицу */
@file_put_contents(data_dir() . '/leads.jsonl', json_encode(['id' => $id, 't' => date('c'), 'type' => $type, 'messenger' => $messenger, 'slot' => $lead['slot'] ?? '', 'site' => $lead['site'] ?? '', 'marks' => $marks], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

respond_and_continue(['ok' => true, 'id' => $id]);

/* ---------- После ответа браузеру: уведомления ---------- */
cards_refresh($id);
$c = $lead['client'] ?? null;
$slotChanged = $prev && (($prev['slot_date'] ?? '') !== ($lead['slot_date'] ?? '') || ($prev['slot_time'] ?? '') !== ($lead['slot_time'] ?? ''));
if ($c && $slotChanged && ($c['m'] ?? '') === $messenger && slot_future($lead)) {
    /* Клиент уже в боте и выбрал на сайте другое время – попросим подтвердить */
    [$t, $b] = client_card($lead);
    bot_send((string)$c['m'], $c['chat'], (!empty($prev['slot_date']) ? 'Вижу, вы выбрали новое время.' : 'Получил вашу запись с сайта.') . "\n\n" . $t, $b);
}
maybe_run_reminders();

function respond_and_continue(array $body): void {
    ignore_user_abort(true);
    http_response_code(200);
    $out = json_encode($body, JSON_UNESCAPED_UNICODE);
    header('Content-Length: ' . strlen((string)$out));
    header('Connection: close');
    echo $out;
    if (ob_get_level() > 0) @ob_end_flush();
    @flush();
    finish_request();
}
