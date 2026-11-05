<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit(1);
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/backups.php';
// Schlüssel kommt von einer sicheren Umgebung, nicht aus Argumenten oder dem Repository.
$encodedKey=getenv('BACKUP_KEY') ?: '';
if(!preg_match('/^[a-f0-9]{64}$/iD',$encodedKey)) throw new InvalidArgumentException('BACKUP_KEY muss 64 Hex-Zeichen enthalten.');
$key=hex2bin($encodedKey); $action=$argv[1]??''; $path=$argv[2]??'';
if(!$path) throw new InvalidArgumentException('Aufruf: php tools/backup.php create|restore PFAD [--confirm-restore]');
$parent=realpath(dirname($path)); $webroot=realpath(dirname(__DIR__).'/public');
if(!$parent || str_starts_with(strtolower(str_replace('\\','/',$parent)).'/',strtolower(str_replace('\\','/',$webroot)).'/')) throw new InvalidArgumentException('Sicherung muss außerhalb des Webverzeichnisses liegen.');
if($action==='create') {
 $blob=encrypt_backup(snapshot_database(),$key);
 $file=fopen($path,'xb'); if(!$file) throw new RuntimeException('Datei existiert bereits oder ist nicht schreibbar.');
 try { if(fwrite($file,$blob)!==strlen($blob)) throw new RuntimeException('Sicherung unvollständig.'); } finally { fclose($file); }
 chmod($path,0600); audit('backup_create','success'); echo "Verschlüsselte Sicherung erstellt.\n";
} elseif($action==='restore') {
 if(($argv[3]??'')!=='--confirm-restore') throw new InvalidArgumentException('Wiederherstellung überschreibt Veranstaltungsdaten. --confirm-restore erforderlich.');
 if(!is_file($path) || filesize($path)>32*1024*1024) throw new InvalidArgumentException('Datei fehlt oder ist größer als 32 MB.');
 restore_database(decrypt_backup(file_get_contents($path),$key)); echo "Sicherung wiederhergestellt; Anmeldung bleibt geschlossen.\n";
} else throw new InvalidArgumentException('Unbekannte Aktion.');
sodium_memzero($key);
