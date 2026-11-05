# Testprotokoll · Version 0.2

Stand: 5. November 2026. PLAN → ENTWICKELN → TESTEN → SICHERHEITSPRÜFUNG → NUTZERTEST → VERBESSERN → ERNEUT TESTEN. Ausschließlich künstliche Daten, kein STRATO-/Produktionszugriff.

## Automatisierte Entwickler- und Sicherheitsprüfung

| Prüfung | Ergebnis | Nachweis |
|---|---|---|
| PHP-Syntax und JavaScript-Syntax | BESTANDEN | PHP 8.4.27, alle Anwendungsskripte inkl. neuer Module; node --check |
| Datenbankfreie Einzelprüfungen | BESTANDEN | tests/unit.php: 46 Prüfungen, Unicode, HTML-Maskierung, exklusive Auswahl, Codeformat/HMAC, CSV-Dubletten/Formelschutz, Sitzungs-/Fristregeln, RFC-6238-Testvektoren, Backupverschlüsselung und Manipulationserkennung |
| Reale DB-Integration | BESTANDEN | tests/integration.php: 34 Prüfungen auf MariaDB 11.4.13; Codes, Änderungen, Sperrung, Neuausgabe, Namensgleichheit, Importrollback, DB-CHECK, Frist/Öffnung, Zwei-Faktor-/Replayprüfung, Restore/Rollback, Rate-Limit |
| Parallele Anmeldungen/Änderungen | BESTANDEN | tests/concurrency.php: zwei PHP-Prozesse, genau eine konsistente Anmeldung je Einwohner |
| Migration V1 → V2 | BESTANDEN | tests/migration.php: Doppelauswahl stoppt Migration; nach Bereinigung Daten erhalten, Codes noch leer, Anmeldung geschlossen |
| HTTP-Regressions- und Live-Testgruppen | BESTANDEN | 11/11 in tests/http.test.mjs und tests/http-live.test.mjs; Code-Anmeldung/-Änderung, JSON, Adminlogin mit TOTP, CSV, Logout, CSRF, Maskierung und Statistik |
| Tatsächliches Backup-CLI und Restore-CLI | BESTANDEN | Verschlüsselte Datei erstellt, mit kurzlebigem Testschlüssel wieder eingespielt; Anmeldung danach geschlossen |
| Desktop und mobile Nutzerprüfung | BESTANDEN | Formular im Browser geprüft; bei 390×844 Pixeln genau ein Radiofeld aktiv, scrollWidth=clientWidth=375, kein horizontaler Überlauf. Einladungscode hat eigenes beschriftetes Pflichtfeld. Aktualisierte Vorschauen im docs-Ordner. |
| Zielhost-TLS, Apache-Regeln und Dateigrenzen | NICHT AUSGEFÜHRT | Kein Zielhost-Zugang; PHP-Testserver wertet .htaccess nicht aus |
| Geplanter Wartungs-/Backupauftrag beim Anbieter | NICHT AUSGEFÜHRT | Kein gebuchtes/zugängliches PHP-Hosting nachgewiesen |
| Rechtliche Einordnung, echte Kontaktdaten, Hostlogs | MANUELL ZU PRÜFEN | Betreiberangaben, Rechtsgrundlage, Aufbewahrung und Hostingvertrag fehlen |
| Vollständige Screenreader-/System-Darkmodeprüfung | MANUELL ZU PRÜFEN | Native Radiofelder, Labels, sichtbarer Fokus und CSS für Systemeinstellungen vorhanden |

## Testumgebung

PHP 8.4.27 aus offizieller Windows-PHP-Distribution, SHA-256 geprüft. Portable MariaDB 11.4.13 von archive.mariadb.org, SHA-256 d62986d433eeebfde218560b276103831604a61e929e87f1a17f5aebd80257e2 geprüft. Datenbank ausschließlich an 127.0.0.1:33307. Keine Installation als Systemdienst. Testkonfiguration, Zugangsdaten, Einladungscodes und DB-Dateien sind ignoriert und werden nicht ins Git-Projekt kopiert.

## Iterative Korrekturen

Aus der bisherigen Version wurden unklare neutrale Erfolgsbestätigungen ohne Identitätsnachweis entfernt. Jetzt gibt es Erfolg erst bei gültiger Code-/Name-Kombination. Gleichnamige Einwohner werden über verschiedene Codes getrennt. CSV-Dubletten werden vor Import und gegen Bestand geprüft; Fehler führen zum vollständigen Rollback. Alte Anmeldungen mit beiden Gerichten werden bei Migration nicht willkürlich umgeschrieben.

Testsetup: MariaDB-Datenverzeichnis musste zuerst angelegt und beim Serverstart als absoluter Pfad angegeben werden. Danach liefen Integration, Parallelität und Migration erfolgreich. Diese Setupfehler waren keine bestandenen Anwendungstests.

## Freigabe

Lokale Funktions- und Sicherheitsprüfungen belegen die getesteten Abläufe; sie sind keine Zusicherung vollständiger Angriffssicherheit. Öffentliches Deployment bleibt bis Hostingentscheidung, vollständigen Betreiberangaben und Zielhost-Abnahme ausstehend. Kein Commit/Push, DNS-Wechsel oder Veröffentlichung durch diesen Arbeitsstand.
