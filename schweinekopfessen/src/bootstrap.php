<?php
declare(strict_types=1);
date_default_timezone_set('Europe/Berlin');
ini_set('display_errors', '0');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store');
$secure = ($_SERVER['HTTPS'] ?? '') === 'on';
if (!$secure && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'],true) && PHP_SAPI !== 'cli') {
 http_response_code(426); exit('Bitte verwende die HTTPS-Adresse dieser Webseite.');
}
if ($secure) header('Strict-Transport-Security: max-age=31536000');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Strict','path'=>'/']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$_SESSION['started'] ??= time();
$configFile = getenv('SKE_CONFIG_FILE') ?: dirname(__DIR__).'/config.php';
$config = array_replace(require dirname(__DIR__).'/config.example.php',is_file($configFile) ? require $configFile : []);
require_once __DIR__.'/security.php';
require_once __DIR__.'/registrations.php';
function e(mixed $v): string { return htmlspecialchars(is_scalar($v) ? (string)$v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function normalize_name(string $v): string {
 $v = Normalizer::normalize($v, Normalizer::FORM_C);
 return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v)), 'UTF-8');
}
function db(): PDO {
 global $config; static $db;
 return $db ??= new PDO($config['dsn'], $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function query(string $sql, array $args=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($args); return $s; }
function csrf(): void {
 if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { http_response_code(403); exit('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.'); }
}
function ready(): bool {
 global $config;
 return strlen($config['secret']) >= 64 && $config['legal_address'] !== '' && filter_var($config['legal_email'], FILTER_VALIDATE_EMAIL) && $config['privacy_basis'] !== '' && $config['delete_after'] !== '';
}
function registration_open(): bool {
 global $config;
 if(!ready() || !registration_window_open($config)) return false;
 return (bool)query('SELECT registration_enabled FROM app_settings WHERE id=1')->fetchColumn();
}
function limit_attempts(string $scope, int $max): bool {
 global $config;
 // Keine Forwarded-Header übernehmen: nur die vom Webserver gelieferte Adresse.
 $key=hash_hmac('sha256', $scope.'|'.($_SERVER['REMOTE_ADDR'] ?? '').'|'.intdiv(time(),900), $config['secret']);
 query('DELETE FROM rate_limits WHERE expires_at < UTC_TIMESTAMP()');
 query('INSERT INTO rate_limits (token,attempts,expires_at) VALUES (?,1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts=attempts+1',[$key]);
 return (int)query('SELECT attempts FROM rate_limits WHERE token=?',[$key])->fetchColumn() <= $max;
}
function audit(string $action, string $result): void {
 global $config; query('INSERT INTO admin_audit_log (action,result,actor_reference) VALUES (?,?,?)',[$action,$result,$config['admin_user']]);
}
function admin(): bool {
 if (empty($_SESSION['admin']) || time()-($_SESSION['last_active'] ?? 0)>1800 || time()-($_SESSION['login_at'] ?? 0)>28800) { unset($_SESSION['admin']); return false; }
 $_SESSION['last_active']=time(); return true;
}
set_exception_handler(function(Throwable $ex): void {
 if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO && $GLOBALS['db']->inTransaction()) $GLOBALS['db']->rollBack();
 error_log('Schweinekopfessen: '.get_class($ex));
 if (PHP_SAPI==='cli') { fwrite(STDERR,'Prüfung fehlgeschlagen: '.$ex->getMessage().PHP_EOL); exit(1); }
 http_response_code(503); echo 'Der Dienst ist vorübergehend nicht verfügbar. Bitte später erneut versuchen.';
});
