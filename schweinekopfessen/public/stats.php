<?php
require dirname(__DIR__).'/src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if (!ready()) { http_response_code(503); echo '{}'; exit; }
$window=max(1,min(60,(int)$config['online_window_minutes']));
if(!limit_attempts('statistics',120)) { http_response_code(429); echo '{}'; exit; }
// Sitzung nur zum Zusammenfassen naher Seitenaufrufe; kein Statistikcookie.
$today=date('Y-m-d'); $yesterday=date('Y-m-d',strtotime('-1 day'));
if (isset($_GET['view']) && time()-($_SESSION['last_view'] ?? 0)>30) {
 query('INSERT INTO visitor_statistics (day,views) VALUES (?,1) ON DUPLICATE KEY UPDATE views=views+1',[$today]);
 $_SESSION['last_view']=time();
}
$token=hash_hmac('sha256',session_id(),$config['secret']);
query('DELETE FROM online_visitors WHERE seen_at < DATE_SUB(UTC_TIMESTAMP(),INTERVAL ? MINUTE)',[$window]);
query('INSERT INTO online_visitors (token,seen_at) VALUES (?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE seen_at=UTC_TIMESTAMP()',[$token]);
$days=query('SELECT day,views FROM visitor_statistics WHERE day IN (?,?)',[$today,$yesterday])->fetchAll(PDO::FETCH_KEY_PAIR);
echo json_encode(['today'=>(int)($days[$today]??0),'yesterday'=>(int)($days[$yesterday]??0),'total'=>(int)query('SELECT COALESCE(SUM(views),0) FROM visitor_statistics')->fetchColumn(),'online'=>(int)query('SELECT COUNT(*) FROM online_visitors')->fetchColumn()]);
