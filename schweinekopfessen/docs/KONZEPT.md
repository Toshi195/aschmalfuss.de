# Anforderungen und Sicherheitskonzept · Version 0.2

Stand: 5. November 2026. Quelle: Chat „Schweinekopfessen organisieren“, 6aec9435-6fd0-83ed-87f6-463c36f4ad9c. Der Master-Prompt konnte diesmal vollständig bis Abschnitt 14 gelesen werden. Die später ausdrücklich festgelegten Entscheidungen haben Vorrang vor der früher erlaubten Doppelauswahl und der nur optionalen Codeprüfung.

## Verbindlicher Ablauf

Ein Veranstaltungslauf: 29. Januar 2027, 19:00 Uhr, Dorfgemeinschaftshaus Kreypau. Anmeldeschluss: 28. Dezember 2026, Tagesende in Europe/Berlin. Keine Besucherkonten. Name, persönlicher Einladungscode und genau ein Gericht sind erforderlich. Exklusive Auswahlkarten mit nativen Radiofeldern ersetzen die Checkboxen. Server und Datenbank erzwingen dieselbe Regel.

Ein gültiger Code gehört zu genau einem Einwohner. Name und Code müssen zusammenpassen; Einwohner muss aktiv und Code ungesperrt sein. Dann wird die Anmeldung atomar erstellt oder die bestehende Essenswahl aktualisiert. Damit sind Namensgleiche sicher unterscheidbar. Besucher erhalten nach verifiziertem Besitz des Codes eine klare Speicherbestätigung. Ungültige Kombinationen erhalten dieselbe neutrale Fehlermeldung ohne Auskunft über vorhandene Einwohner, Codes oder Anmeldungen.

Jede Person erhält einen kryptografisch zufälligen Code mit 128 Bit Entropie. In der DB steht nur HMAC-SHA-256 unter dem Anwendungsschlüssel. Klartext erscheint einmal bei Ausgabe in der geschützten Administration, nicht in URL, CSV-Export, Audit oder Log. Code wird persönlich verteilt. Neuausgabe sperrt alten Code sofort; Sperrung oder Inaktivierung verhindert neue Anmeldungen und Änderungen, löscht aber nicht automatisch bestehende Planungseinträge.

Ein Code ist ein Besitznachweis. Weitergabe ermöglicht fremde Änderungen. Kontrollierte Ausgabe und Neuausgabe sowie sicherer Umgang mit einmal angezeigten Codes sind Betreiberaufgaben.

## Anforderungsabgleich

| Anforderung | Umsetzung |
|---|---|
| Name, genau eine Speise, kein Besucherlogin | Formular und JSON-API, Radiofelder, serverseitige Prüfung, DB-CHECK |
| Einwohnerabgleich, Dubletten und Parallelität | Code-HMAC mit Unique-Index, Resident-Fremdschlüssel und Unique-Anmeldung, InnoDB-Transaktion |
| Änderung bis Frist | Derselbe Name und Code, atomare Aktualisierung |
| Heute/Gestern/Gesamt/Online | Berliner Kalendertage, aggregierte Aufrufe, SitzungshMAC, konfigurierbares Aktivitätsfenster |
| Sichere Administration | Passwort + TOTP, sitzungsgebundener CSRF-Schutz, Sitzungsrotation und Ablauf |
| Einwohnerpflege | CSV-Datei/Text, zeilenbezogene Validierung, Dublettenerkennung und Importrollback, Einzelanlage, Bearbeitung/Inaktivierung |
| Codeverwaltung | Neue Ausgabe und Sperrung, keine erneute Klartextanzeige |
| Teilnehmerverwaltung | Summen, Gerichtskorrektur, Einzellöschung, CSV-Export |
| Anmeldezeitraum | Betriebssperre in Konfiguration, Adminschalter, automatische Frist |
| Backups/Restore | Verschlüsseltes CLI-Werkzeug, Schema-/Integritätsprüfung, transaktionales Restore, Wiederherstellung getestet |
| Impressum/Datenschutz | Auszufüllende Betreiberangaben; keine erfundenen Kontaktdaten, Codeverarbeitung ergänzt |
| Responsive Gestaltung | Eigene Apple-inspirierte Typografie/Weißraum, Hell-/Dunkelmodus nach System, reduzierte Bewegung |
| Dokumentation/Selbstkontrolle | Architektur, API, Betrieb, Migration, Hostingvergleich, Tests und Änderungsverlauf |

## Technische Grenzen und Schutzmaßnahmen

- SQL-Injection: native PDO-Parameterbindung. SQL-Bezeichner kommen nur aus festen Allowlisten. Namensnormalisierung: Unicode NFC, einheitlicher Leerraum, Kleinschreibung; keine unscharfe Suche.
- XSS: maskierte Ausgaben und restriktive CSP, keine Drittanbieter-Schriften oder -Skripte. CSV-Zellen bekommen bei möglichen Tabellenformeln ein schützendes Präfix.
- CSRF: zufälliger sitzungsgebundener Token, Änderungen nur per POST, SameSite=Strict. JSON-API benötigt zusätzlich X-CSRF-Token; kein freigegebenes Cross-Origin-CORS.
- Rate-Limits: öffentliche Formular- und API-Anmeldung teilen sich acht Versuche pro 15 Minuten je IP-Zeitfenster, Adminlogin fünf, administrative Aktionen und Statistik 120. Kurzlebige IP-HMACs, keine vollständigen IP-Adressen in Anwendungstabellen. Grenzen bei verteilten Angreifern und gemeinsamem Internetanschluss bleiben.
- Bots: Honeypot, Mindestformularzeit, Antwortverzögerung und große Codeentropie; keine externen CAPTCHAs.
- Admin: Passwort-Hash, 6-stellige TOTP nach RFC 6238 mit ±1 Zeitschritt. Jeder Zeitcode wird DB-atomar nur einmal akzeptiert. Sessionrotation, 30 Minuten Inaktivität, 8 Stunden Maximaldauer, HttpOnly, Secure über HTTPS. Fehlender TOTP-Schlüssel sperrt Login. Wiederherstellung des zweiten Faktors ausschließlich kontrolliert außerhalb der öffentlichen Seite.
- Datenbank: genau eine Anmeldung je resident_id; ein Veranstaltungslauf je Installation. Kein Mehrveranstaltungsmodell. DB-CHECK verlangt genau ein Gericht. Migration bricht bei unbereinigten Doppelauswahlen ab. Bestehende Namen und Essensdaten bleiben bei erfolgreicher Migration erhalten.
- Öffnen/Schließen und Speichern sperren dieselbe app_settings-Zeile, damit eine parallel geschlossene Anmeldung keine neue Speicherung durchlässt.
- HTTPS: Webserverweiterleitung auf die Zieldomain und zusätzliche PHP-Sperre für unverschlüsselten Nicht-Loopback-Zugriff. Proxy-Header werden nicht blind vertraut. Für Reverse-Proxy-Hosting muss die tatsächliche HTTPS-Signalisierung separat geprüft werden.
- Audit: Anmeldung/Abmeldung des Admins, Codeaktionen, Einwohnerpflege, Export, Planungskorrektur, Öffnung, Backup und Restore; keine Klartextcodes oder Passwörter im Audit.
- Dateien/Secrets: nur public/ als Webroot. config.php und optionale SKE_CONFIG_FILE-Konfiguration außerhalb. Nur minimale DB-Rechte im Betrieb. Backups enthalten keine Konfiguration, Adminpasswörter oder TOTP-Schlüssel. Anwendungsschlüssel separat sichern, da Code-Prüfwerte sonst nicht mehr nutzbar sind.

## Datenschutz und Zählmethodik

Einwohnerliste und Exporte sind nie öffentlich abrufbar. Keine Echtdaten im Git-Repository. Verarbeitung nur für Veranstaltungsplanung, nach vom Verantwortlichen zu klärender Rechtsgrundlage. Rechteinformation und Impressum sind noch zu vervollständigender Entwurf. Tatsächliches STRATO-Paket, Hostingverträge, Logfristen und rechtliche Einordnung müssen vor Betrieb geprüft werden.

Heute/Gestern zählen Seitenaufrufe nach Europe/Berlin, Gesamt summiert Tagesaggregate seit Aktivierung. Wiederholungen derselben Sitzung innerhalb von 30 Sekunden werden zusammengefasst. Keine eindeutige Menschenzählung. Online schätzt aktive Sitzungen im eingestellten Fenster (1–60 Minuten, Standard 5). Der technisch erforderliche Sitzungscookie wird für Formularschutz und Verwaltung verwendet; die Statistik setzt keinen zusätzlichen Tracker. SitzungshMACs werden nach dem Aktivitätsfenster entfernt; Rate-HMACs nach Ablauf. Keine dauerhafte Voll-IP-Speicherung durch die Anwendung, keine Kontrolle über separate Hostlogs.

Täglicher Wartungsauftrag bereinigt Online-/Rate-Daten, Audits nach 90 Tagen und Einwohner/Anmeldungen nach konfiguriertem Löschdatum; er schließt dann die Anmeldung. Ohne laufenden Wartungsauftrag funktioniert zeitnahe Bereinigung nur bei passenden Folgeaufrufen. Export- und Backupkopien müssen in das Löschkonzept einbezogen werden; Backupfristen separat festlegen.

## Quellen und offene Abnahme

Gestaltungsreferenz: https://www.apple.com/de/apple-watch-series-12/ (keine Apple-Assets übernommen).
CSRF: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
TOTP und geprüfte Testvektoren: https://www.rfc-editor.org/rfc/rfc6238
Passwort-API: https://www.php.net/manual/en/function.password-hash.php

Lokale Tests ersetzen keine Zielhost-Abnahme. Hostingzugriff, echte Betreiber-/Datenschutzangaben, TLS, öffentliche Dateigrenzen, Backupauftrag, Wiederherstellung auf dem Zielhost und Assistenztechnik bleiben vor Freigabe zu prüfen. Siehe TESTPROTOKOLL.md.
