<?php
declare(strict_types=1);
const BACKUP_COLUMNS = [
 'residents'=>['id','first_name','last_name','normalized_name','active','invitation_hash','invitation_revoked','created_at','updated_at'],
 'registrations'=>['id','resident_id','wants_schweinekopf','wants_eisbein','status','created_at','updated_at'],
 'visitor_statistics'=>['day','views'],
 'app_settings'=>['id','registration_enabled'],
 'admin_audit_log'=>['id','action','timestamp','result','actor_reference'],
];
function encrypt_backup(array $data,string $key): string {
 if(strlen($key)!==SODIUM_CRYPTO_SECRETBOX_KEYBYTES) throw new InvalidArgumentException('Backup-Schlüssel muss 32 Bytes enthalten.');
 $nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
 return 'SKE2'.$nonce.sodium_crypto_secretbox(json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$nonce,$key);
}
function decrypt_backup(string $blob,string $key): array {
 if(strlen($key)!==32 || strlen($blob)<44 || substr($blob,0,4)!=='SKE2') throw new InvalidArgumentException('Ungültiges Sicherungsformat oder Schlüssel.');
 $plain=sodium_crypto_secretbox_open(substr($blob,28),substr($blob,4,24),$key);
 if($plain===false) throw new InvalidArgumentException('Sicherung manipuliert oder Schlüssel falsch.');
 return json_decode($plain,true,512,JSON_THROW_ON_ERROR);
}
function snapshot_database(): array {
 $connection=db(); $connection->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'); $connection->beginTransaction();
 try {
  $data=['version'=>2,'created_at'=>gmdate(DATE_ATOM),'tables'=>[]];
  foreach(BACKUP_COLUMNS as $table=>$columns) $data['tables'][$table]=query('SELECT '.implode(',',$columns).' FROM '.$table)->fetchAll();
  $connection->commit(); return $data;
 } catch(Throwable $ex) { if($connection->inTransaction()) $connection->rollBack(); throw $ex; }
}
function restore_database(array $data): void {
 if(($data['version']??null)!==2 || array_keys($data['tables']??[])!==array_keys(BACKUP_COLUMNS)) throw new InvalidArgumentException('Sicherung passt nicht zum Schema Version 2.');
 foreach(BACKUP_COLUMNS as $table=>$columns) {
  if(!is_array($data['tables'][$table])) throw new InvalidArgumentException('Ungültige Tabelle.');
  foreach($data['tables'][$table] as $row) {
   if(!is_array($row) || array_keys($row)!==$columns) throw new InvalidArgumentException('Ungültige Spalten.');
   foreach($row as $value) if(!is_scalar($value) && $value!==null) throw new InvalidArgumentException('Ungültiger Sicherungswert.');
  }
 }
 $connection=db(); $connection->beginTransaction();
 try {
  // Zuerst die Öffnung sperren; dieselbe Zeile wird von Anmeldungen gesperrt.
  query('SELECT id FROM app_settings WHERE id=1 FOR UPDATE');
  foreach(['registrations','residents','visitor_statistics','app_settings','admin_audit_log'] as $table) query('DELETE FROM '.$table);
  foreach(BACKUP_COLUMNS as $table=>$columns) foreach($data['tables'][$table] as $row) {
   query('INSERT INTO '.$table.' ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')',array_values($row));
  }
  // Nach Wiederherstellung immer bewusst erneut freigeben.
  query('UPDATE app_settings SET registration_enabled=0 WHERE id=1');
  audit('backup_restore','success'); $connection->commit();
 } catch(Throwable $ex) { if($connection->inTransaction()) $connection->rollBack(); throw $ex; }
}
