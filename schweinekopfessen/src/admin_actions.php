<?php
declare(strict_types=1);

function accept_admin_login(string $user,string $password,string $otp): bool {
 global $config;
 if(!$config['admin_password_hash'] || !preg_match('/^[A-Z2-7]{32,128}$/D',$config['admin_totp_secret'])) return false;
 $passwordValid=password_verify($password,$config['admin_password_hash']);
 $step=totp_step($config['admin_totp_secret'],$otp);
 if(!$passwordValid || !hash_equals($config['admin_user'],$user) || $step===null) return false;
 query('INSERT IGNORE INTO admin_auth_state (actor_reference,last_totp_step) VALUES (?,-1)',[$config['admin_user']]);
 return query('UPDATE admin_auth_state SET last_totp_step=? WHERE actor_reference=? AND last_totp_step<?',[$step,$config['admin_user'],$step])->rowCount()===1;
}
function import_residents(string $csv): int {
 $rows=parse_residents_csv($csv); $connection=db(); $connection->beginTransaction();
 try {
  query('SELECT id FROM app_settings WHERE id=1 FOR UPDATE');
  foreach($rows as $row) {
   if(query('SELECT id FROM residents WHERE normalized_name=? LIMIT 1',[$row[2]])->fetchColumn()) throw new InvalidArgumentException('Name bereits vorhanden. Nichts importiert; Namensgleiche bitte einzeln anlegen.');
   query('INSERT INTO residents (first_name,last_name,normalized_name) VALUES (?,?,?)',$row);
  }
  audit('residents_import','success'); $connection->commit(); return count($rows);
 } catch(Throwable $ex) { if($connection->inTransaction()) $connection->rollBack(); throw $ex; }
}
function update_resident(int $id,string $first,string $last,bool $active): void {
 if(!valid_name_part($first) || !valid_name_part($last)) throw new InvalidArgumentException('Bitte gültigen Vor- und Nachnamen eintragen.');
 if($id===0) {
  query('INSERT INTO residents (first_name,last_name,normalized_name,active) VALUES (?,?,?,?)',[$first,$last,normalize_name($first.' '.$last),(int)$active]);
  audit('resident_create','success');
 } else {
  query('UPDATE residents SET first_name=?,last_name=?,normalized_name=?,active=? WHERE id=?',[$first,$last,normalize_name($first.' '.$last),(int)$active,$id]);
  audit('resident_update:'.$id,'success');
 }
}
function rotate_invitation(int $id): string {
 $code=new_invitation();
 if(query('UPDATE residents SET invitation_hash=?,invitation_revoked=0 WHERE id=? AND active=1',[invitation_hash($code),$id])->rowCount()!==1) throw new InvalidArgumentException('Aktiver Einwohner nicht gefunden.');
 audit('invitation_rotate:'.$id,'success'); return $code;
}
function export_registrations(): never {
 audit('registration_export','success');
 header('Content-Type: text/csv; charset=utf-8');
 header('Content-Disposition: attachment; filename="schweinekopfessen-anmeldungen.csv"');
 $stream=fopen('php://output','w'); fwrite($stream,"\xEF\xBB\xBF");
 fputcsv($stream,['Vorname','Nachname','Gericht','Status','Angemeldet','Aktualisiert'],';','"','');
 foreach(query('SELECT r.first_name,r.last_name,g.* FROM registrations g JOIN residents r ON r.id=g.resident_id ORDER BY r.last_name,r.first_name') as $row) {
  fputcsv($stream,array_map('csv_safe',[$row['first_name'],$row['last_name'],$row['wants_schweinekopf']?'Schweinekopf':'Eisbein',$row['status'],$row['created_at'],$row['updated_at']]),';','"','');
 }
 fclose($stream); exit;
}
