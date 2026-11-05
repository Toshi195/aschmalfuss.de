<?php
declare(strict_types=1);

function save_registration(string $name,string $code,string $meal): bool {
 global $config;
 $flags=meal_flags($meal);
 if(!valid_person_name($name) || !$flags || !preg_match('/^[a-f0-9]{32}$/D',normalize_invitation($code))) return false;
 $connection=db(); $connection->beginTransaction();
 try {
  // Auch die Öffnung wird innerhalb der Transaktion geprüft: Schließen und Speichern sind serialisiert.
  $enabled=query('SELECT registration_enabled FROM app_settings WHERE id=1 FOR UPDATE')->fetchColumn();
  if(!(bool)$enabled || !registration_window_open($config)) { $connection->rollBack(); return false; }
  $resident=query('SELECT id,normalized_name,active,invitation_revoked FROM residents WHERE invitation_hash=? FOR UPDATE',[invitation_hash($code)])->fetch();
  if(!$resident || !$resident['active'] || $resident['invitation_revoked'] || !hash_equals($resident['normalized_name'],normalize_name($name))) {
   $connection->rollBack(); return false;
  }
  query("INSERT INTO registrations (resident_id,wants_schweinekopf,wants_eisbein,status) VALUES (?,?,?,'confirmed') ON DUPLICATE KEY UPDATE wants_schweinekopf=VALUES(wants_schweinekopf),wants_eisbein=VALUES(wants_eisbein),status='confirmed',updated_at=CURRENT_TIMESTAMP",[$resident['id'],...$flags]);
  $connection->commit(); return true;
 } catch(Throwable $ex) { if($connection->inTransaction()) $connection->rollBack(); throw $ex; }
}
