<?php
/*
 * Мастер подключения ботов. Откройте один раз после загрузки сайта на хостинг:
 *   https://<ваш-сайт>/api/setup.php?key=<setup_key из config.php>
 * Что делает: проверяет хостинг, проверяет токены, подключает вебхуки Telegram и Max,
 * показывает, кто назначен администратором. Можно открывать повторно – ничего не сломается.
 *   &info=1 – только показать состояние, ничего не менять
 *   &test=1 – прислать администраторам тестовое сообщение
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$key = (string)cfg('setup_key', '');
if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Доступ закрыт. Откройте эту страницу с параметром ?key=<setup_key из api/config.php>.\n";
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');

$rows = [];
function row(string $title, bool $ok, string $note = '', bool $warn = false): void {
    global $rows;
    $rows[] = [$title, $ok ? ($warn ? 'warn' : 'ok') : 'fail', $note];
}
$infoOnly = !empty($_GET['info']);

/* ---------- Хостинг ---------- */
row('PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '7.4.0', '>='), 'нужен 7.4 или новее');
row('cURL', function_exists('curl_init') || ini_get('allow_url_fopen'), function_exists('curl_init') ? '' : 'cURL нет, работаем через allow_url_fopen', !function_exists('curl_init'));
row('mbstring', function_exists('mb_substr'), function_exists('mb_substr') ? '' : 'желательно включить в панели хостинга', !function_exists('mb_substr'));
$dir = data_dir();
row('Папка данных доступна на запись', is_writable($dir), $dir);

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$root = rtrim(str_replace('\\', '/', dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/api/setup.php')))), '/');
$base = (string)cfg('site_url', '') !== '' ? rtrim((string)cfg('site_url'), '/') : 'https://' . $host . $root;
row('HTTPS', $https, $https ? $base : 'вебхуки Telegram и Max работают только по HTTPS – включите SSL-сертификат в панели хостинга');
if (!$infoOnly) json_write($dir . '/runtime.json', ['site_url' => $base, 't' => date('c')]);

/* Закрыта ли папка с данными от посторонних */
$probe = http_json($base . '/api/data/index.html');
row('Папка data закрыта от посторонних', $probe['code'] === 403 || $probe['code'] === 404 || $probe['code'] === 0, $probe['code'] === 200 ? 'файлы открываются из браузера! Для nginx добавьте: location /api/data { deny all; }' : 'код ответа ' . $probe['code'], $probe['code'] === 0);

$secret = (string)cfg('webhook_secret', '');

/* ---------- Telegram ---------- */
if (tg_enabled()) {
    $me = tg_call('getMe', []);
    row('Telegram: токен', $me !== null, $me ? '@' . ($me['username'] ?? '') : 'Telegram не принял токен или хостинг не достучался до api.telegram.org – смотрите api/data/bot.log');
    if ($me && !$infoOnly) {
        if (cfg('telegram.mode', 'webhook') === 'poll') {
            $wh = tg_call('deleteWebhook', ['drop_pending_updates' => false]);
            row('Telegram: режим опроса (вебхук отключён)', $wh !== null, 'сообщения забирает api/poll.php по планировщику каждую минуту');
        } else {
            $wh = tg_call('setWebhook', ['url' => $base . '/api/tg.php', 'secret_token' => $secret, 'allowed_updates' => ['message', 'callback_query'], 'drop_pending_updates' => true]);
            row('Telegram: вебхук', $wh !== null, $base . '/api/tg.php');
        }
        tg_call('setMyCommands', ['commands' => [['command' => 'start', 'description' => 'Записаться на онлайн-разбор']]]);
        /* Имя, описание и аватарку бота оформляем один раз вручную (BotFather / Bot API) – здесь не перезаписываем */
    }
    if ($me && cfg('telegram.mode', 'webhook') !== 'poll') {
        $info = tg_call('getWebhookInfo', []);
        if ($info) {
            $err = (string)($info['last_error_message'] ?? '');
            row('Telegram: доставка обновлений', $err === '', $err !== '' ? 'последняя ошибка: ' . $err : 'ошибок нет, в очереди: ' . (int)($info['pending_update_count'] ?? 0));
        }
    }
} else {
    row('Telegram', false, 'не указан telegram.token в config.php');
}

/* ---------- Max ---------- */
if (max_enabled()) {
    $full = null;
    $me = max_call('GET', 'me', [], null, $full);
    $note = $me ? ('@' . ($me['username'] ?? '') . ' · ' . ($me['first_name'] ?? $me['name'] ?? '')) : ('ответ ' . ($full['code'] ?? 0) . ' ' . ($full['error'] ?? '') . ' ' . ($full['raw'] ?? ''));
    if (!$me && $full && stripos((string)$full['error'], 'SSL') !== false) {
        $note .= ' – на хостинге нет корневого сертификата Минцифры. Скачайте «Russian Trusted Root CA» с gosuslugi.ru/crt, положите файл рядом с config.php и укажите путь в max.ca_file.';
    }
    row('Max: токен', $me !== null, $note);
    if ($me) {
        $subs = max_call('GET', 'subscriptions');
        $want = $base . '/api/max.php';
        $have = false;
        foreach ((array)($subs['subscriptions'] ?? []) as $s) {
            if (($s['url'] ?? '') === $want) { $have = true; continue; }
            if (!$infoOnly && !empty($s['url'])) max_call('DELETE', 'subscriptions', ['url' => $s['url']]);
        }
        if (!$infoOnly) {
            $r = max_call('POST', 'subscriptions', [], ['url' => $want, 'update_types' => ['bot_started', 'message_created', 'message_callback'], 'secret' => $secret]);
            row('Max: вебхук', !empty($r['success']), $r === null ? 'Max не принял подписку – смотрите api/data/bot.log' : $want);
        } else {
            row('Max: вебхук', $have, $have ? $want : 'не подключён');
        }
    }
} else {
    row('Max', false, 'не указан max.token в config.php', true);
}

/* ---------- Администраторы ---------- */
$adm = admins();
$count = count($adm['telegram']) + count($adm['max']);
$code = (string)cfg('admin_code', '');
row('Администраторы', $count > 0, $count > 0
    ? 'Telegram: ' . (count($adm['telegram']) ?: 'нет') . ' · Max: ' . (count($adm['max']) ?: 'нет')
    : 'пока никого – напишите боту команду ниже', $count === 0);

if (!empty($_GET['test']) && $count > 0) {
    foreach ($adm as $m => $chats) foreach ($chats as $chat) bot_send($m, $chat, '🔧 Проверка связи: бот подключён, заявки будут приходить сюда.');
    row('Тестовое сообщение', true, 'отправлено администраторам');
}

$tgBot = (string)cfg('telegram.bot', '');
$maxBot = (string)cfg('max.bot', '');
$self = strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?') . '?key=' . rawurlencode($key);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Подключение ботов</title>
<style>
  body { margin: 0; padding: 32px 16px; background: #0B1020; color: #E7ECF7; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
  main { max-width: 760px; margin: 0 auto; }
  h1 { font-size: 26px; margin: 0 0 20px; }
  h2 { font-size: 18px; margin: 32px 0 12px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 10px 12px; border-top: 1px solid #1F2A44; vertical-align: top; }
  td:first-child { width: 38%; font-weight: 600; }
  .ok::before { content: "✓ "; color: #6EE7A8; } .warn::before { content: "! "; color: #FBBF24; } .fail::before { content: "✗ "; color: #F87171; }
  small { color: #9AA6C2; }
  code { background: #141C33; padding: 3px 8px; border-radius: 6px; font-size: 15px; user-select: all; }
  a { color: #C6F25E; }
  .btns a { display: inline-block; margin: 0 12px 8px 0; }
</style>
</head>
<body>
<main>
  <h1>Подключение ботов</h1>
  <table>
    <?php foreach ($rows as $r): ?>
      <tr><td class="<?= h($r[1]) ?>"><?= h($r[0]) ?></td><td><small><?= h($r[2]) ?></small></td></tr>
    <?php endforeach; ?>
  </table>

  <h2>Как стать администратором</h2>
  <p>Откройте бота и отправьте ему команду:</p>
  <p><code>/admin <?= h($code) ?></code></p>
  <p class="btns">
    <?php if ($tgBot !== ''): ?><a href="https://t.me/<?= h($tgBot) ?>" target="_blank" rel="noopener">Открыть бота в Telegram</a><?php endif; ?>
    <?php if ($maxBot !== ''): ?><a href="https://max.ru/<?= h($maxBot) ?>" target="_blank" rel="noopener">Открыть бота в Max</a><?php endif; ?>
  </p>
  <p><small>Достаточно одного мессенджера: заявки и сообщения клиентов из обоих ботов придут туда, где вы администратор, и ответить клиенту можно оттуда же.</small></p>

  <h2>Дальше</h2>
  <p class="btns">
    <a href="<?= h($self) ?>&amp;info=1">Обновить состояние</a>
    <a href="<?= h($self) ?>&amp;test=1">Прислать тестовое сообщение</a>
  </p>
  <p><small>Напоминания: добавьте в планировщик хостинга задачу каждые 5 минут – <code>php <?= h(__DIR__) ?>/cron.php</code><br>
  или по ссылке: <code><?= h($base) ?>/api/cron.php?key=<?= h($key) ?></code></small></p>
</main>
</body>
</html>
