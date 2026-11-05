<?php
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/admin_actions.php';
require dirname(__DIR__).'/src/backups.php';
$database=db()->query('SELECT DATABASE()')->fetchColumn();
if(!str_ends_with($database,'_test') || db()->query('SHOW TABLES')->fetchColumn()!==false) throw new RuntimeException('Neue leere _test-Datenbank erforderlich.');
foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/schema.sql')) as $sql) if(trim($sql)) db()->exec($sql);
$count=0;
function verify(bool $ok,string $label): void { global $count; if(!$ok) throw new RuntimeException($label); $count++; }
verify(!registration_open(),'DB-Öffnungsschalter standardmäßig aus');
query('UPDATE app_settings SET registration_enabled=1 WHERE id=1'); verify(registration_open(),'DB-Öffnungsschalter wirksam');
verify(import_residents("Max;Muster\nErika;Beispiel")===2,'Einwohnerimport');
try { import_residents("Neu;Beispiel\nMax;Muster"); verify(false,'Bestandsdubletten'); } catch(InvalidArgumentException) { verify((int)query('SELECT COUNT(*) FROM residents')->fetchColumn()===2,'Import komplett zurückgerollt'); }
$id=(int)query("SELECT id FROM residents WHERE first_name='Max'")->fetchColumn();
$code=rotate_invitation($id); verify(query('SELECT invitation_hash FROM residents WHERE id=?',[$id])->fetchColumn()===invitation_hash($code),'Nur Code-Prüfwert gespeichert');
verify(save_registration('MAX Muster',$code,'schweinekopf'),'Berechtigte Anmeldung');
verify(save_registration('Max Muster',$code,'eisbein'),'Änderung mit Code');
verify((int)query('SELECT COUNT(*) FROM registrations')->fetchColumn()===1 && (int)query('SELECT wants_eisbein FROM registrations')->fetchColumn()===1,'Eine Anmeldung, korrigiertes Gericht');
verify(!save_registration('Erika Beispiel',$code,'schweinekopf'),'Code nicht fremdem Namen zugeordnet');
verify(!save_registration('Max Muster',new_invitation(),'eisbein'),'Falscher Code');
verify(!save_registration('Max Muster',$code,'both'),'Beide Gerichte abgewiesen');
query('UPDATE residents SET invitation_revoked=1 WHERE id=?',[$id]); verify(!save_registration('Max Muster',$code,'eisbein'),'Gesperrter Code');
$newCode=rotate_invitation($id); verify(!save_registration('Max Muster',$code,'eisbein') && save_registration('Max Muster',$newCode,'schweinekopf'),'Neuausgabe invalidiert alten Code');
update_resident(0,'Max','Muster',true); $second=(int)query('SELECT MAX(id) FROM residents')->fetchColumn(); $secondCode=rotate_invitation($second);
verify(save_registration('Max Muster',$secondCode,'eisbein'),'Namensgleichheit mit eigenem Code');
verify((int)query('SELECT COUNT(*) FROM registrations')->fetchColumn()===2,'Getrennte Anmeldungen für Namensgleiche');
query('UPDATE residents SET active=0 WHERE id=?',[$id]); verify(!save_registration('Max Muster',$newCode,'eisbein'),'Inaktive Einwohner'); query('UPDATE residents SET active=1 WHERE id=?',[$id]);
$config['registration_deadline']='2000-01-01T00:00:00+01:00'; verify(!save_registration('Max Muster',$newCode,'eisbein'),'Änderung nach Frist abgewiesen'); $config['registration_deadline']='2099-01-01';
query('UPDATE app_settings SET registration_enabled=0 WHERE id=1'); verify(!save_registration('Max Muster',$newCode,'eisbein'),'Geschlossene Anmeldung'); query('UPDATE app_settings SET registration_enabled=1 WHERE id=1');
try { query('UPDATE registrations SET wants_schweinekopf=1,wants_eisbein=1'); verify(false,'DB akzeptiert beide Gerichte'); } catch(PDOException) { verify(true,'CHECK schützt Gerichtswahl'); }
$password=json_decode(file_get_contents(dirname(__DIR__).'/tools/test-data/access.json'),true)['password']; $otp=totp($config['admin_totp_secret'],intdiv(time(),30));
verify(!accept_admin_login($config['admin_user'],$password,'000000') || $otp==='000000','Falscher zweiter Faktor');
verify(accept_admin_login($config['admin_user'],$password,$otp),'Zwei-Faktor-Login');
verify(!accept_admin_login($config['admin_user'],$password,$otp),'TOTP-Replay verhindert');
$key=random_bytes(32); $snapshot=snapshot_database(); $blob=encrypt_backup($snapshot,$key); query('DELETE FROM registrations');
restore_database(decrypt_backup($blob,$key)); verify((int)query('SELECT COUNT(*) FROM registrations')->fetchColumn()===2,'Backup stellt Anmeldungen wieder her'); verify(!registration_open(),'Restore schließt Anmeldung');
query('UPDATE app_settings SET registration_enabled=1 WHERE id=1');
$bad=$snapshot; $bad['tables']['registrations'][0]['wants_schweinekopf']=1; $bad['tables']['registrations'][0]['wants_eisbein']=1;
try { restore_database($bad); verify(false,'Fehlerhaftes Restore akzeptiert'); } catch(PDOException) { verify((int)query('SELECT COUNT(*) FROM registrations')->fetchColumn()===2,'Restorefehler vollständig zurückgerollt'); }
for($i=0;$i<8;$i++) verify(limit_attempts('integration-rate',8),'Limit erlaubt gültige Versuche');
verify(!limit_attempts('integration-rate',8),'Limit sperrt weitere Versuche');
query('DELETE FROM rate_limits'); query('DELETE FROM admin_auth_state');
file_put_contents(dirname(__DIR__).'/tools/test-data/invitations.json',json_encode(['id'=>$id,'code'=>$newCode,'second_id'=>$second,'second_code'=>$secondCode],JSON_THROW_ON_ERROR));
echo "$count Datenbankprüfungen BESTANDEN. Nur künstliche Testdaten.\n";
