# Schweinekopfessen · aschmalfuss.de

Veranstaltung: **29. Januar 2027, 19:00 Uhr, Dorfgemeinschaftshaus Kreypau**. Anmeldung bis **28. Dezember 2026, 23:59:59 Europe/Berlin**.

Aktueller Stand: lokale PHP-/MySQL-Anwendung, noch nicht veröffentlicht. Änderungen aus dem vollständig abgerufenen Master-Prompt und den späteren Entscheidungen wurden eingearbeitet: persönlicher Einladungscode ohne Besucherkonto, genau ein Gericht, Änderung per Code, CSV-Export, Einwohnerpflege, zweiter Adminfaktor und verschlüsselte Backups.

## Einstieg

- [Anforderungen und Sicherheitskonzept](docs/KONZEPT.md)
- [Architektur und Schnittstellen](docs/ARCHITEKTUR.md)
- [Installation, Migration, Backup und Wartung](docs/BETRIEB.md)
- [Hostingprüfung und offene Entscheidungen](docs/HOSTING.md)
- [Testprotokoll](docs/TESTPROTOKOLL.md)
- [Änderungsprotokoll](docs/AENDERUNGEN.md)

Benötigt: unterstützte PHP-Version ab 8.3 mit PDO MySQL, intl, mbstring und sodium; MySQL 8.0.16+ oder kompatible MariaDB mit tatsächlich erzwungenen CHECK-Constraints, InnoDB und HTTPS. Keine Composer-/Frontend-Pakete nötig.

Nur **public/** darf öffentlich erreichbar sein. Konfiguration, Datenbankdateien, Backups und Werkzeuge bleiben außerhalb. config.example.php enthält keine Zugangsdaten. Eine echte config.php wird nicht in Git aufgenommen.

## Installation und bestehende Daten

Neue Datenbank: database/schema.sql importieren. Bestehende Version 1: zuerst verschlüsseltes Backup bzw. Hosting-Datenbanksicherung erstellen, Daten mit beiden Gerichten persönlich bereinigen, dann database/migrations/002_invitation_codes.sql einmal anwenden. Niemals das Neuanlageschema über bestehende Daten spielen.

Konfiguration vervollständigen und Authenticator einrichten, Einwohner importieren, Codes einzeln persönlich ausgeben. Jeder Code wird mit 128 Bit Zufall erzeugt und nur als HMAC-Prüfwert gespeichert. Ein neuer Code ersetzt den alten sofort. Codeverlust erfordert kontrollierte Neuausgabe.

Die Anmeldung öffnet erst, wenn Betriebskonfiguration vollständig ist, registration_open=true gesetzt wurde, der Adminschalter geöffnet ist und die Frist noch läuft. Nach Wiederherstellung bleibt sie bewusst geschlossen.

## Tests

`php tests/unit.php` prüft unabhängig von einer Datenbank. `node --test tests/http.test.mjs` benötigt die geschlossene Standardvorschau auf 127.0.0.1:8088. Live-Tests sind ausschließlich für künstliche Daten auf einem separaten Testschema bestimmt; Vorbereitung und Reihenfolge stehen in docs/BETRIEB.md. **tests/http-live.test.mjs niemals gegen Produktion ausführen.**

Tests wurden lokal mit PHP 8.4.27 und MariaDB 11.4.13 durchgeführt. Ergebnis und Grenzen stehen im Testprotokoll. Die Testlaufzeiten und künstlichen Zugangsdaten sind nicht Teil des Git-Projekts.

## Veröffentlichung

Das Repository liegt unter GIT/aschmalfuss.de/schweinekopfessen. GitHub Pages führt diese PHP-Anwendung nicht aus. Das zuvor genannte STRATO-Domainpaket ist noch nicht als PHP-/MySQL-Hosting bestätigt. Zwischen geeignetem Webhosting und getrennter Oberfläche/Backend ist noch zu entscheiden. Es wurden keine Tarife gebucht und keine DNS-Einstellungen geändert.

Vor Veröffentlichung fehlen insbesondere echte Anschrift und Kontakt-E-Mail, bestätigte Rechtsgrundlage und Löschfrist, Hostingzugriff und Abnahme auf dem Zielhost. Die gespeicherten Webseiten-Vorschauen zeigen die lokale Gestaltung, keine Live-Veröffentlichung.
