<?php
require dirname(__DIR__).'/src/bootstrap.php';
require dirname(__DIR__).'/src/admin_actions.php';
$notice=''; $issuedCode=''; $issuedName=''; $logged=admin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
 csrf(); $action=is_string($_POST['action']??null)?$_POST['action']:'';
 if($action==='login' && !$logged) {
  if(!ready() || !limit_attempts('admin-login',5)) { http_response_code(429); $notice='Anmeldung derzeit nicht möglich. Bitte später erneut versuchen.'; }
  else {
   if(accept_admin_login(is_string($_POST['user']??null)?$_POST['user']:'',is_string($_POST['password']??null)?$_POST['password']:'',is_string($_POST['otp']??null)?$_POST['otp']:'')) {
    session_regenerate_id(true); $_SESSION['admin']=true; $_SESSION['last_active']=time(); $_SESSION['login_at']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32));
    audit('login','success'); header('Location: /admin.php',true,303); exit;
   }
   audit('login','failure'); usleep(300000); $notice='Anmeldung fehlgeschlagen.';
  }
 } elseif($logged) {
  if($action==='logout') { audit('logout','success'); $_SESSION=[]; session_regenerate_id(true); header('Location: /admin.php',true,303); exit; }
  if(!limit_attempts('admin-actions',120)) { http_response_code(429); $notice='Zu viele Verwaltungsaktionen. Bitte später erneut versuchen.'; }
  else {
   $id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);
   try {
    switch($action) {
     case 'export': export_registrations();
     case 'import':
      $csv=is_string($_POST['residents']??null)?$_POST['residents']:'';
      if(isset($_FILES['csv']) && $_FILES['csv']['error']!==UPLOAD_ERR_NO_FILE) {
       if($_FILES['csv']['error']!==UPLOAD_ERR_OK || $_FILES['csv']['size']>100000 || !is_uploaded_file($_FILES['csv']['tmp_name'])) throw new InvalidArgumentException('CSV-Datei konnte nicht gelesen werden. Maximal 100 KB.');
       $csv=file_get_contents($_FILES['csv']['tmp_name']);
      }
      $notice=import_residents($csv).' Einwohner importiert. Einladungscodes bitte persönlich ausgeben.'; break;
     case 'resident_save':
      if($id===false || $id<0) throw new InvalidArgumentException('Ungültiger Einwohner.');
      update_resident($id,is_string($_POST['first']??null)?trim($_POST['first']):'',is_string($_POST['last']??null)?trim($_POST['last']):'',($_POST['active']??'')==='1');
      $notice='Einwohner gespeichert.'; break;
     case 'code_rotate':
      if(!$id) throw new InvalidArgumentException('Ungültiger Einwohner.');
      $issuedName=query('SELECT CONCAT(first_name,\' \',last_name) FROM residents WHERE id=?',[$id])->fetchColumn();
      $issuedCode=rotate_invitation($id); $notice='Neuer Einladungscode. Der bisherige Code ist sofort ungültig. Dieser Code wird nur jetzt angezeigt.'; break;
     case 'code_revoke':
      if(!$id) throw new InvalidArgumentException('Ungültiger Einwohner.');
      query('UPDATE residents SET invitation_revoked=1 WHERE id=?',[$id]); audit('invitation_revoke:'.$id,'success'); $notice='Code gesperrt. Bestehende Anmeldung bleibt erhalten.'; break;
     case 'correct':
      $flags=meal_flags($_POST['meal']??null); if(!$id || !$flags) throw new InvalidArgumentException('Anmeldung oder Gericht ungültig.');
      query('UPDATE registrations SET wants_schweinekopf=?,wants_eisbein=?,status=\'confirmed\' WHERE id=?',[...$flags,$id]); audit('registration_correct:'.$id,'success'); $notice='Essenswunsch korrigiert.'; break;
     case 'cancel':
      if(!$id) throw new InvalidArgumentException('Ungültige Anmeldung.');
      query('DELETE FROM registrations WHERE id=?',[$id]); audit('registration_delete:'.$id,'success'); $notice='Anmeldung gelöscht. Code weiterhin verwendbar, solange die Anmeldung geöffnet ist.'; break;
     case 'registration_toggle':
      query('UPDATE app_settings SET registration_enabled=? WHERE id=1',[($_POST['enabled']??'')==='1'?1:0]); audit('registration_toggle','success'); $notice='Anmeldeschalter gespeichert. Betriebssperre und Frist gelten weiterhin.'; break;
     case 'purge':
      if(($_POST['confirm']??'')!=='LOESCHEN') throw new InvalidArgumentException('Zum endgültigen Löschen LOESCHEN eingeben.');
      $db=db(); $db->beginTransaction(); query('UPDATE app_settings SET registration_enabled=0 WHERE id=1'); query('DELETE FROM registrations'); query('DELETE FROM residents'); audit('event_data_delete','success'); $db->commit(); $notice='Veranstaltungsdaten gelöscht und Anmeldung geschlossen.'; break;
     default: throw new InvalidArgumentException('Unbekannte Verwaltungsaktion.');
    }
   } catch(InvalidArgumentException $ex) { $notice=$ex->getMessage(); }
   $_SESSION['csrf']=bin2hex(random_bytes(32));
  }
 } else { http_response_code(403); $notice='Bitte zuerst anmelden.'; }
}
$registrations=$logged ? query('SELECT g.*,r.first_name,r.last_name FROM registrations g JOIN residents r ON r.id=g.resident_id ORDER BY r.last_name,r.first_name')->fetchAll():[];
$residents=$logged ? query('SELECT * FROM residents ORDER BY last_name,first_name,id')->fetchAll():[];
$enabled=$logged ? (bool)query('SELECT registration_enabled FROM app_settings WHERE id=1')->fetchColumn():false;
function token(): void { echo '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Administration · Schweinekopfessen</title><link rel="stylesheet" href="/style.css"></head><body><main class="admin"><a href="/">← Zur Webseite</a><h1>Administration.</h1>
<?php if($notice):?><p class="notice" role="status"><?=e($notice)?></p><?php endif;?>
<?php if($issuedCode):?><div class="notice"><strong><?=e($issuedName)?></strong><p>Persönlicher Einladungscode:</p><code class="invitation-code"><?=e($issuedCode)?></code><p>Persönlich und vertraulich übergeben. Kein Versand durch die Webseite.</p></div><?php endif;?>
<?php if(!$logged):?>
<p>Geschützter Bereich für die Veranstaltungsplanung.</p><form method="post"><?php token();?><input type="hidden" name="action" value="login"><label for="user">Benutzername</label><input id="user" name="user" required autocomplete="username"><label for="password">Passwort</label><input id="password" name="password" type="password" required autocomplete="current-password" maxlength="1024"><label for="otp">Code aus der Authenticator-App</label><input id="otp" name="otp" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"><button class="pill">Anmelden</button></form>
<?php else:?>
<form method="post"><?php token();?><button name="action" value="logout">Abmelden</button></form>
<h2>Anmeldezeitraum</h2><p>Aktuell: <?=registration_open()?'geöffnet':'geschlossen'?> · Ende: <?=e($config['registration_deadline'])?></p><p>Der Schalter öffnet nur bei vollständiger Betriebskonfiguration und innerhalb der Frist.</p><form method="post"><?php token();?><label class="choice"><input type="checkbox" name="enabled" value="1" <?=$enabled?'checked':''?>>Anmeldung freigeben</label><button name="action" value="registration_toggle">Schalter speichern</button></form>
<h2>Essensplanung</h2><p><?=count($registrations)?> Personen · <?=array_sum(array_column($registrations,'wants_schweinekopf'))?> Schweinekopf · <?=array_sum(array_column($registrations,'wants_eisbein'))?> Eisbein</p>
<form method="post"><?php token();?><button name="action" value="export">Anmeldungen als CSV exportieren</button></form>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Gericht</th><th>Verwalten</th></tr></thead><tbody><?php foreach($registrations as $r):?><tr><td><?=e($r['first_name'].' '.$r['last_name'])?></td><td><?=$r['wants_schweinekopf']?'Schweinekopf':'Eisbein'?></td><td><form method="post"><?php token();?><input type="hidden" name="id" value="<?=e($r['id'])?>"><label>Gericht korrigieren<select name="meal"><option value="schweinekopf" <?=$r['wants_schweinekopf']?'selected':''?>>Schweinekopf</option><option value="eisbein" <?=$r['wants_eisbein']?'selected':''?>>Eisbein</option></select></label><button name="action" value="correct">Speichern</button><button name="action" value="cancel">Anmeldung löschen</button></form></td></tr><?php endforeach;?></tbody></table></div>
<h2>Einwohner und Einladungscodes</h2><p>Ein neuer Code ersetzt den bisherigen. Codes werden nur bei Ausgabe angezeigt und sonst als Prüfwert gespeichert. Jede Person erhält einen eigenen Code, auch bei Namensgleichheit.</p>
<?php foreach($residents as $r):?><details class="resident"><summary><?=e($r['first_name'].' '.$r['last_name'])?> · #<?=e($r['id'])?> · <?=$r['active']?'aktiv':'inaktiv'?> · <?=$r['invitation_hash'] ? ($r['invitation_revoked']?'Code gesperrt':'Code ausgegeben'):'ohne Code'?></summary><form method="post"><?php token();?><input type="hidden" name="id" value="<?=e($r['id'])?>"><label>Vorname<input name="first" value="<?=e($r['first_name'])?>" maxlength="100" required></label><label>Nachname<input name="last" value="<?=e($r['last_name'])?>" maxlength="100" required></label><label class="choice"><input type="checkbox" name="active" value="1" <?=$r['active']?'checked':''?>>Aktiver Einwohner</label><button name="action" value="resident_save">Einwohner speichern</button><button name="action" value="code_rotate">Neuen Code ausgeben</button><button name="action" value="code_revoke">Code sperren</button></form></details><?php endforeach;?>
<h2>Einwohner einzeln anlegen</h2><p>Auch für bewusst geprüfte Namensgleichheit. Keine Person doppelt anlegen.</p><form method="post"><?php token();?><input type="hidden" name="id" value="0"><input type="hidden" name="active" value="1"><label>Vorname<input name="first" maxlength="100" required></label><label>Nachname<input name="last" maxlength="100" required></label><button name="action" value="resident_save">Einwohner anlegen</button></form>
<h2>Einwohner importieren</h2><p>UTF-8-CSV ohne Überschrift: <code>Vorname;Nachname</code>. Bei Dubletten oder ungültigen Zeilen wird der gesamte Import zurückgerollt.</p><form method="post" enctype="multipart/form-data"><?php token();?><label for="csv">CSV-Datei (maximal 100 KB)</label><input id="csv" type="file" name="csv" accept=".csv,text/csv"><label for="residents">Oder CSV-Text</label><textarea id="residents" name="residents" maxlength="100000"></textarea><button name="action" value="import">Importieren</button></form>
<h2>Datensicherung und Wiederherstellung</h2><p>Verschlüsselte Sicherungen werden mit dem Wartungswerkzeug außerhalb des öffentlichen Webverzeichnisses erstellt und wiederhergestellt. Anleitung: Projektdokumentation BETRIEB.md.</p>
<h2>Veranstaltungsdaten endgültig löschen</h2><form method="post"><?php token();?><label for="confirm">Zur Bestätigung LOESCHEN eingeben</label><input id="confirm" name="confirm" required pattern="LOESCHEN"><button name="action" value="purge">Alle Einwohner und Anmeldungen löschen</button></form>
<?php endif;?></main></body></html>
