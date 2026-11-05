<?php
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/backups.php';
$count=0;
function check(bool $condition,string $label): void { global $count; if(!$condition) throw new RuntimeException($label); $count++; }
check(normalize_name("  ANDRÉ\t Schmalfuß ")==='andré schmalfuß','Whitespace und Großschreibung');
check(normalize_name("Andre\u{0301} Schmalfuß")===normalize_name('André Schmalfuß'),'Unicode-Komposition');
check(normalize_name('Jörg Müller')!==normalize_name('Jorg Muller'),'Keine unscharfe Zuordnung');
check(normalize_name('Anne-Marie O’Neill')==='anne-marie o’neill','Namenszeichen');
check(e('<script>"\'')==='&lt;script&gt;&quot;&#039;','HTML-Ausgabe maskiert');
check(e(['name'])==='','Manipulierte Array-Eingabe');
check(!ready(),'Unvollständige Konfiguration gesperrt');
check(!registration_open(),'Anmeldung standardmäßig geschlossen');
$config['secret']=str_repeat('a',64); $config['legal_address']='Teststraße 1'; $config['legal_email']='test@example.org'; $config['privacy_basis']='TEST'; $config['delete_after']='2027-01-01';
check(ready(),'Vollständigkeitsprüfung');
$config['registration_open']=true;
$config['registration_deadline']='';
check(registration_window_open($config),'Öffnung konfigurierbar');
$config['registration_deadline']='2000-01-01T00:00:00+01:00'; check(!registration_window_open($config),'Anmeldeschluss');
$config['registration_deadline']='invalid'; check(!registration_window_open($config),'Ungültiger Anmeldeschluss schließt');
$_SESSION['admin']=true; $_SESSION['last_active']=time(); $_SESSION['login_at']=time(); check(admin(),'Gültige Adminsitzung');
$_SESSION['last_active']=time()-1801; check(!admin(),'Inaktivitätslimit');
$_SESSION['admin']=true; $_SESSION['last_active']=time(); $_SESSION['login_at']=time()-28801; check(!admin(),'Absolutes Sitzungslimit');
check(meal_flags('schweinekopf')===[1,0] && meal_flags('eisbein')===[0,1],'Exklusive Gerichte');
foreach([null,[],['schweinekopf','eisbein'],'both','',1] as $meal) check(meal_flags($meal)===null,'Manipulierte Auswahl abgewiesen');
check(valid_person_name('Anne-Marie O’Neill'),'Gültiger Name');
check(!valid_person_name("Max <script>alert(1)</script>"),'HTML-Namen abgewiesen');
$code=new_invitation(); check(strlen(normalize_invitation($code))===32,'128-Bit-Codeformat');
check(invitation_hash($code)===invitation_hash(strtolower(str_replace('-',' ',$code))),'Codenormalisierung');
check(invitation_hash(new_invitation())!==invitation_hash($code),'Unabhängige Codes');
check(!str_contains(invitation_hash($code),normalize_invitation($code)),'Kein Klartextcode im Prüfwert');
check(count(parse_residents_csv("Max;Muster\nErika;Beispiel"))===2,'CSV-Import');
foreach(["Max;Muster\nMAX;Muster","Max;Muster;extra","<script>;Muster",''] as $bad) {
 try { parse_residents_csv($bad); check(false,'Ungültige CSV akzeptiert'); } catch(InvalidArgumentException) { check(true,'Ungültige CSV erkannt'); }
}
check(csv_safe('=1+1')==="'=1+1" && csv_safe(' @cmd')==="' @cmd" && csv_safe('Max')==='Max','CSV-Formel-Injection');
// RFC 6238 Appendix B, SHA-1-Testvektoren (8 Stellen).
$totpSecret='GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
foreach([59=>'94287082',1111111109=>'07081804',1111111111=>'14050471',1234567890=>'89005924',2000000000=>'69279037',20000000000=>'65353130'] as $time=>$expected) check(totp($totpSecret,intdiv($time,30),8)===$expected,'RFC-TOTP-Vektor');
check(totp_step($totpSecret,totp($totpSecret,100),3000)===100,'TOTP akzeptiert');
check(totp_step($totpSecret,totp($totpSecret,90),3000)===null,'Alter TOTP abgewiesen');
$key=random_bytes(32); $payload=['version'=>2,'tables'=>['name'=>'Übung']];
$blob=encrypt_backup($payload,$key); check(decrypt_backup($blob,$key)===$payload,'Verschlüsselter Backup-Roundtrip');
foreach([$blob.'x',substr_replace($blob,'x',35,1)] as $tampered) {
 try { decrypt_backup($tampered,$key); check(false,'Manipuliertes Backup akzeptiert'); } catch(InvalidArgumentException) { check(true,'Manipuliertes Backup verworfen'); }
}
try { decrypt_backup($blob,random_bytes(32)); check(false,'Falscher Schlüssel akzeptiert'); } catch(InvalidArgumentException) { check(true,'Falscher Schlüssel verworfen'); }
echo "$count Prüfungen BESTANDEN.\n";
