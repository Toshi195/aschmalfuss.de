<?php
require dirname(__DIR__).'/src/bootstrap.php';
$message=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 csrf();
 if (!registration_open()) $error='Die Anmeldung ist derzeit geschlossen.';
 elseif (!limit_attempts('register',8)) { http_response_code(429); $error='Bitte versuche es in 15 Minuten erneut.'; }
 else {
  $name=is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
  $code=is_string($_POST['invitation'] ?? null) ? $_POST['invitation'] : '';
  $meal=is_string($_POST['meal'] ?? null) ? $_POST['meal'] : '';
  if (!valid_person_name($name)) $error='Bitte gib deinen vollständigen Vor- und Nachnamen ein.';
  elseif (!meal_flags($meal)) $error='Bitte wähle genau ein Gericht.';
  else {
   $start=microtime(true);
   $saved=false;
   if (empty($_POST['website']) && time()-$_SESSION['started']>=2) {
    $saved=save_registration($name,$code,$meal);
   }
   usleep(max(0,(int)((0.35-(microtime(true)-$start))*1000000)));
   if($saved) {
    $_SESSION['receipt']='Dein Essenswunsch wurde gespeichert. Zum Ändern kannst du bis zum Anmeldeschluss denselben Namen und Einladungscode erneut verwenden.';
    header('Location: /index.php#anmeldung',true,303); exit;
   }
   $error='Die Anmeldung war nicht möglich. Bitte prüfe Name und Einladungscode oder wende dich an André.';
  }
 }
}
$message=$_SESSION['receipt'] ?? ''; unset($_SESSION['receipt']);
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Gemeinsam genießen. Anmeldung zum Schweinekopfessen."><title>Schweinekopfessen · Gemeinsam genießen</title><link rel="stylesheet" href="/style.css"><script src="/app.js" defer></script></head><body>
<a class="skip" href="#anmeldung">Zur Anmeldung</a><header><a class="brand" href="/">Unsere Heimat<span>KREYPAU · WÜSTENEUTZSCH · WÖLKAU</span></a><nav><a href="#gerichte">Die Gerichte</a><a class="pill small" href="#anmeldung">Anmelden</a></nav></header>
<main><section class="hero"><div class="hero-copy"><p class="eyebrow">GEMEINSAM AM TISCH · 2027</p><h1>Tradition, die<br>uns <em>verbindet.</em></h1><p class="intro">Ein geselliger Abend, ein herzhaftes Essen und gute Gesellschaft. Wir treffen uns zum traditionellen großen Schweinekopfessen in Kreypau.</p><a class="pill" href="#anmeldung">Zum Anmeldebereich <span aria-hidden="true">↗</span></a><p class="event"><?=e($config['event_details'])?></p></div><a class="poster" href="/assets/plakat.jpeg" target="_blank" rel="noopener" aria-label="Veranstaltungsplakat in voller Größe öffnen"><img src="/assets/plakat.jpeg" alt="Veranstaltungsplakat zum Schweinekopfessen mit Gruppenfoto und Heimatmotiv" width="1024" height="1536"></a></section>
<section id="gerichte" class="section"><p class="eyebrow">DU HAST DIE WAHL</p><h2>Herzhaft. Gesellig.<br>Ganz nach deinem Geschmack.</h2><div class="dishes"><article><span class="number">01</span><h3>Schweinekopf</h3><p>Der Klassiker für unseren gemeinsamen Abend.</p><span class="dish-word" aria-hidden="true">Tradition.</span></article><article><span class="number">02</span><h3>Eisbein</h3><p>Deftig genießen. In bester Gesellschaft.</p><span class="dish-word" aria-hidden="true">Genuss.</span></article></div></section>
<section id="anmeldung" class="section signup"><div><p class="eyebrow">DEIN PLATZ AM TISCH</p><h2>Vorfreude?<br>Eintragen.</h2><p>Dein Name. Dein Einladungscode.<br>Dein Lieblingsgericht.</p><p class="muted">Du brauchst kein Benutzerkonto. Deinen persönlichen Einladungscode erhältst du von André. Mit ihm kannst du deinen Essenswunsch bis zum Anmeldeschluss ändern. Bitte gib den Code nicht weiter.</p></div><div class="form-card">
<?php if ($message): ?><div class="notice" role="status"><?=e($message)?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error" role="alert"><?=e($error)?></div><?php endif; ?>
<?php if (!registration_open()): ?><p class="notice">Die Anmeldung ist derzeit geschlossen. Informationen erhältst du bei André.</p><?php endif; ?>
<form method="post" action="/#anmeldung">
<input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>">
<label for="name">Vor- und Nachname</label><input id="name" name="name" autocomplete="name" maxlength="200" required placeholder="Dein vollständiger Name" value="<?=e($_POST['name'] ?? '')?>">
<label for="invitation">Persönlicher Einladungscode</label><input id="invitation" name="invitation" autocomplete="off" spellcheck="false" maxlength="64" required aria-describedby="code-hint" placeholder="Dein Code aus der Einladung"><p id="code-hint" class="hint">Bindestriche und Leerzeichen sind erlaubt. Bei Verlust wende dich bitte an André.</p>
<div class="honey" aria-hidden="true"><label>Webseite<input name="website" tabindex="-1" autocomplete="off"></label></div>
<fieldset><legend>Worauf freust du dich?</legend><p class="hint">Bitte genau ein Gericht wählen.</p>
<label class="choice"><input type="radio" name="meal" value="schweinekopf" required <?=($_POST['meal']??'')==='schweinekopf'?'checked':''?>><span>Schweinekopf</span></label>
<label class="choice"><input type="radio" name="meal" value="eisbein" required <?=($_POST['meal']??'')==='eisbein'?'checked':''?>><span>Eisbein</span></label></fieldset>
<p class="hint">Wir verarbeiten deinen Namen und Essenswunsch für die Veranstaltungsplanung. Details im <a href="/legal.php#datenschutz">Datenschutzhinweis</a>.</p><button class="pill" <?=!registration_open()?'disabled':''?>>Essenswunsch speichern <span aria-hidden="true">↗</span></button></form></div></section>
<section class="stats" aria-label="Besucherstatistik"><div><strong data-stat="today">–</strong><span>Heute</span></div><div><strong data-stat="yesterday">–</strong><span>Gestern</span></div><div><strong data-stat="total">–</strong><span>Gesamt</span></div><div><strong data-stat="online">–</strong><span>Online ≈</span></div><p id="stats-note">Datensparsame Seitenaufrufe · Online geschätzt: letzte <?=e(max(1,min(60,(int)$config['online_window_minutes'])))?> Minuten</p></section></main><footer><span>Gemeinsam genießen. © <?=date('Y')?> André Schmalfuß</span><div><a href="/legal.php">Impressum</a><a href="/legal.php#datenschutz">Datenschutz</a><a href="/admin.php">Administration</a></div></footer></body></html>
