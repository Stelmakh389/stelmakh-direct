<?php
/*
 * Свободное время для записи на сайте: {"ok":true,"tz":"Europe/Moscow","days":[{"date":"2026-10-26","slots":["10:00","14:00"]}, …]}
 * Прошедшие дни, выходные и занятое другими клиентами время сюда не попадают.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$days = [];
foreach (booking_free_days() as $d) $days[] = ['date' => $d['iso'], 'slots' => $d['slots']];
echo json_encode(['ok' => true, 'tz' => 'Europe/Moscow', 'now' => date('c'), 'days' => $days], JSON_UNESCAPED_UNICODE);
