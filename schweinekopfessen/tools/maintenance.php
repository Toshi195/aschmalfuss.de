<?php
// CLI oder STRATO-Auftragsplanung. Kein öffentlicher Endpunkt.
if (PHP_SAPI !== 'cli') exit(1);
date_default_timezone_set('Europe/Berlin');
$config=array_replace(require dirname(__DIR__).'/config.example.php',require dirname(__DIR__).'/config.php');
$db=new PDO($config['dsn'],$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('DELETE FROM admin_audit_log WHERE timestamp < DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 DAY)');
$db->exec('DELETE FROM rate_limits WHERE expires_at < UTC_TIMESTAMP()');
$window=max(1,min(60,(int)$config['online_window_minutes']));
$db->exec('DELETE FROM online_visitors WHERE seen_at < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$window.' MINUTE)');
if ($config['delete_after'] && date('Y-m-d') > $config['delete_after']) {
 $db->beginTransaction(); $db->exec('UPDATE app_settings SET registration_enabled=0 WHERE id=1'); $db->exec('DELETE FROM registrations'); $db->exec('DELETE FROM residents'); $db->commit();
}
echo "Wartung abgeschlossen.\n";
