# Änderungen

## 0.2 · 5. November 2026

Vollständiger Master-Prompt erneut abgerufen, diesmal Abschnitte 1–14 verfügbar. Neueste Nutzerentscheidungen übernommen und ursprüngliche Annahmen korrigiert.

- Genau ein Gericht per Radioauswahl, PHP-Prüfung und DB-CHECK. allow_both entfällt.
- Persönliche 128-Bit-Einladungscodes, nur HMAC in DB, kontrollierte Einmalausgabe, Sperren und Neuausgabe.
- Eindeutige Zuordnung auch bei Namensgleichheit; klare Bestätigung erst nach Codeprüfung. Änderung derselben Anmeldung bis Fristende möglich.
- Administration um TOTP mit Replay-Schutz, CSV-Export mit Formelschutz, Einwohnerpflege, CSV-Dateiupload mit Dubletten-/Rollbackprüfung, Gerichtskorrektur und Öffnungsschalter ergänzt.
- Verschlüsselte Backups und transaktionales Restore als nicht öffentliches CLI-Werkzeug. Wiederherstellung schließt Anmeldeformular.
- Datensparsame Statistik mit konfigurierbarem Onlinefenster und Rate-Limit auch für Aktualisierungsaufrufe.
- Same-Origin-JSON-API, Architektur-/Betriebsdokumentation und Migrationspfad ergänzt.
- STRATO-Webhosting ist nicht mehr als nachgewiesene Voraussetzung dargestellt. Domain-Tarif stammt aus dem verknüpften Chat; tatsächliches Paket und Deploymentweg bleiben zu prüfen.
- Erstmals reale lokale MariaDB-Integration, Parallelitäts-, Migrations-, Backup-/Restore- und Live-HTTP-Tests statt alleiniger Schema-/Oberflächenprüfung.

## 0.1 · 5. November 2026

Erste lokale PHP-/MySQL-Vorabversion mit Name, optional beiden Gerichten, Adminpasswort und Statistik erstellt. Damals war nur ein gekürzter Prompt bis Datenbankabschnitt verfügbar. Anschließend nach GIT/aschmalfuss.de/schweinekopfessen kopiert. Keine Veröffentlichung.


## Plakatgestaltung zusammengeführt · 5. November 2026

Das bereitgestellte Veranstaltungsplakat ist als lokale Bilddatei integriert. PHP-Anwendung und statische Vorschau verwenden denselben Einstiegsbereich mit Creme-/Grüntönen und responsiver Plakatansicht. Einladungscodeprüfung, CSRF-Schutz, Speisenauswahl, Verwaltung und Statistik bleiben Bestandteil der bestehenden Anwendung. Der Vorschau-Export berücksichtigt die relativen Bildpfade. Die statische Vorschau nimmt weiterhin keine Anmeldungen entgegen.

Validierung hier ausgeführt: PHP-Syntax von Anwendung und Export, 46 Einzelprüfungen, Vorschau neu aus PHP exportiert und Desktop-/Mobilansicht bei 390 und 320 Pixeln geprüft. Bei 320 Pixeln wurde der Abstand zwischen Heimat-Schriftzug und Anmeldeknopf verbessert. Plakat lädt korrekt; die Vorschau bleibt ohne Formularübermittlung, Sitzungstoken und Backendaufrufe.
