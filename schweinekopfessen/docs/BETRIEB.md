# Installation, Migration, Sicherung und Wartung

## Installation

Unterstütztes PHP ab 8.3, PDO MySQL, intl, mbstring und sodium aktivieren. DB muss InnoDB und CHECK-Constraints erzwingen (MySQL ab 8.0.16 bzw. kompatible MariaDB). Für neue Installation schema.sql einmalig in eine neue private Datenbank importieren. Laufender DB-Nutzer nur SELECT/INSERT/UPDATE/DELETE auf Anwendungstabellen; DDL-Rechte nur für Installation/Migration separat verwenden.

config.example.php nach config.php außerhalb public/ kopieren. Alternativ den absoluten externen Konfigurationspfad über SKE_CONFIG_FILE in der vertrauenswürdigen Serverumgebung setzen. DB-Verbindung und 64 Hex-Zeichen aus `bin2hex(random_bytes(32))` als secret setzen. Keine Geheimnisse in Git, Browsercode, URLs oder Shellargumente schreiben. Alle Prozesse müssen denselben Anwendungsschlüssel verwenden.

Starkes, einzigartiges Adminpasswort lokal mit PHP password_hash(..., PASSWORD_DEFAULT) hashen. Den Hash und Benutzernamen konfigurieren. Einen zufällig erzeugten Base32-Schlüssel mit mindestens 160 Bit in eine Authenticator-App aufnehmen, denselben Schlüssel als admin_totp_secret außerhalb des Webroots hinterlegen. Empfohlen: 20 Zufallsbytes, als 32 Base32-Zeichen A–Z/2–7 ohne Padding. Keine im Repository enthaltenen Testschlüssel verwenden. Serverzeit per NTP synchronisieren. Kontrollierte Wiederherstellung des zweiten Faktors nur durch den Betreiber mit Hostingzugriff; im Notfall Passwort/TOTP-Schlüssel außerhalb des Webs ersetzen und Adminsitzungen serverseitig beenden.

Anschrift, Kontakt-E-Mail, passende Rechtsgrundlage und Löschdatum YYYY-MM-DD ergänzen. Hostinglog-Fristen und rechtliche Einordnung prüfen. Echte Einwohnerliste erst bei geklärter Berechtigung importieren. `registration_open=true` als Betriebserlaubnis setzen; Adminschalter bleibt zunächst aus. Einladungen im Adminbereich einzeln ausgeben, vertraulich übergeben. Keine Klartextcodes exportieren oder in gemeinsame Tabellen kopieren. Bei Verlust neuen Code ausgeben, nachdem die Person persönlich geprüft wurde.

**Nur public/ ist Dokumentwurzel.** .htaccess setzt Apache voraus. HTTP wird auf https://aschmalfuss.de umgeleitet. PHP lehnt zusätzlich unverschlüsselten Zugriff von außerhalb Loopback ab; Reverse-Proxy-Konfiguration nicht ungeprüft übernehmen. Secrets, Werkzeuge, Docs, Tests und SQL dürfen nicht über die Domain erreichbar sein.

Zielhost mit künstlichen Daten testen, danach Testdaten entfernen, echte Daten importieren und im Adminbereich öffnen. Name+Code+Gericht ist die vollständige öffentliche Anmeldung. Dieselben Angaben ändern die Essenswahl bis Fristende. Code-Sperrung und Inaktivierung verhindern künftige Änderungen; bestehende Planungseinträge bei Bedarf administrativ korrigieren oder löschen.

## Migration Version 1 → 2

1. Anwendung schließen und DB-/Konfigurationssicherung erstellen. Für Version 1 ein vorhandenes Hosting-Backup oder mysqldump verwenden; das neue strukturierte Backupwerkzeug setzt Schema 2 voraus.
2. `SELECT * FROM registrations WHERE wants_schweinekopf + wants_eisbein <> 1;` ausführen. Jede Doppelauswahl persönlich klären; keine automatische Wahl oder Datenlöschung.
3. database/migrations/002_invitation_codes.sql **einmal** ausführen. DDL kann vom Datenbanksystem implizit festgeschrieben werden: nicht mehrfach blind wiederholen, bei Teilfehlern Status prüfen bzw. Backup zurückspielen.
4. Neue Konfigurationsfelder ergänzen, allow_both entfernen. Bestehende config.php erhält fehlende Standardfelder automatisch, dadurch ist TOTP ohne eingerichteten Schlüssel gesperrt.
5. Bestehende Einwohner haben zunächst keinen Code. Pro Person kontrolliert Code ausgeben. Vorhandene Registrierungen bleiben bestehen. Öffnungsschalter steht nach Migration auf geschlossen.
6. Anwendung mit Testfällen für Name/Code, Änderung, Frist, Export und Administration prüfen, erst dann öffnen.

## Verschlüsseltes Backup und Restore

CLI-Werkzeug: `php tools/backup.php create ABSOLUTER_PFAD.backup`. Separaten zufälligen 32-Byte-Schlüssel als 64 Hex-Zeichen über die geschützte Umgebungsvariable BACKUP_KEY bereitstellen; nicht in Argumenten, Logs oder Git ablegen. Datei wird nur neu erstellt, nicht überschrieben. Zielverzeichnis außerhalb public/ wählen; Zugriffsrechte und Offsite-Verschlüsselung prüfen.

Format SKE2: JSON-Snapshot mit Versionskennung, verschlüsselt und authentifiziert mit libsodium secretbox. Gesichert werden Einwohner einschließlich Code-Prüfwerten, Anmeldungen, Tagesstatistik, Anwendungsschalter und Auditprotokoll. Keine Klartextcodes, keine config.php, Adminpasswörter, Authenticator-Schlüssel oder kurzlebigen Sitzungstabellen.

Anwendungsschlüssel/DB-Konfiguration und BACKUP_KEY separat gesichert aufbewahren. Ohne ursprünglichen Anwendungsschlüssel passen die wiederhergestellten Code-Prüfwerte nicht mehr; ohne Backupschlüssel lässt sich die Sicherung nicht entschlüsseln. Konfigurations-/Dateisicherungen und Hosting-DB-Backup zusätzlich vorsehen, weil das Anwendungsbackup kein vollständiges Serverabbild ist.

Wiederherstellung: zuerst Anwendung für Besucher schließen, aktuelle Sicherung erstellen, passende Schema-Version sicherstellen. Dann `php tools/backup.php restore ABSOLUTER_PFAD.backup --confirm-restore`. Überschreibt Veranstaltungsdaten; nur nach bewusster Entscheidung verwenden. Falsche Schlüssel oder manipulierte Dateien scheitern vor Datenänderung. Schema-/DB-Fehler führen zum Rollback. Danach bleibt die Anmeldung geschlossen; Datenstände und Codeprüfung kontrollieren, anschließend bewusst freigeben.

Mindestens täglich sichern, regelmäßig Wiederherstellung auf separatem Testschema nachweisen. Backupkopien und CSV-Exporte nur so lange aufbewahren wie festgelegt; nach Löschtermin auch aus Sicherungen fristgerecht entfernen. Keine Sicherungen im Repository speichern. Betreiber muss Zeitplan, konkrete Aufbewahrungsfrist und Restoreverantwortung noch festlegen.

## Wartung

tools/maintenance.php täglich über eine echte CLI-/STRATO-Auftragsplanung ausführen. Bereinigt kurzlebige Daten, Audits nach 90 Tagen und nach delete_after alle Einwohner/Anmeldungen; schließt Registrierung. PHP-Sicherheitsupdates einspielen, DB-/Hostupdates überwachen, Zugriffe und fehlgeschlagene Sicherungen prüfen. Kein öffentliches Wartungs- oder Restore-URL anlegen.

## Lokale Tests reproduzieren

Nur künstliche Daten verwenden. Benötigt eine selbst verwaltete lokale DB, die ausschließlich an Loopback gebunden ist. Die hier verwendete portable Testlaufzeit ist nicht mit ins Git-Projekt kopiert.

1. Eine **leere** Datenbank mit Namensendung _test anlegen. TEST_MYSQL_DSN, TEST_MYSQL_USER und ggf. TEST_MYSQL_PASSWORD in der vertrauenswürdigen Testumgebung setzen. tests/prepare-local.php erzeugt die nicht versionierte Konfiguration unter tools/test-data; es verweigert vorhandene Testkonfigurationen.
2. SKE_CONFIG_FILE auf den absoluten tools/test-data/test.config.php-Pfad setzen, tests/integration.php ausführen. Es verlangt ein leeres _test-Schema und hinterlässt ausschließlich künstliche Daten für Folgetests. Anschließend tests/concurrency.php ausführen.
3. Separate neue leere _test-Datenbank über TEST_MIGRATION_DSN für tests/migration.php verwenden. Prüft Sperre bei Alt-Doppelauswahl und Datenerhalt. tests/mysql.php prüft die Grundconstraints alternativ in einer weiteren leeren Testdatenbank.
4. Ohne SKE_CONFIG_FILE Standardserver auf 127.0.0.1:8088 mit -t public starten. Separaten Server mit SKE_CONFIG_FILE auf 127.0.0.1:8089 starten. `node --test tests/http.test.mjs tests/http-live.test.mjs` ausführen. Die Live-Suite ist nur für diese Testkonfiguration mit dem ausdrücklich öffentlichen RFC-Testschlüssel vorgesehen, niemals für Produktion.
5. Backups/Restore zusätzlich über das CLI-Werkzeug mit eigens erzeugtem Test-Backupschlüssel ausführen. Alle Testserver danach beenden.

tests/unit.php ohne SKE_CONFIG_FILE starten. Bei fehlschlagenden Prüfungen liefert der PHP-Handler einen Fehlerexitcode, statt einen Erfolg vorzutäuschen. HTTPS-/Webserverregeln sind mit dem PHP-Testserver nicht abgenommen; hierfür braucht es den Zielhost.
