# Hostingprüfung · noch keine Anbieterentscheidung

Stand: 5. November 2026. Aus dem verknüpften Chat wurde als bisheriges Paket „STRATO Domain .de“ und eine Verbindung zu GitHub Pages berichtet. Das ist Gesprächskontext, keine Einsicht in das tatsächliche STRATO-Kundenkonto. In diesem Auftrag liegt kein STRATO-Vertrags-/Hostingzugang vor.

Verifiziert am lokalen Repository: origin ist https://github.com/Toshi195/aschmalfuss.de.git, Branch main, CNAME enthält aschmalfuss.de. Daraus allein folgt nicht, dass DNS, HTTPS oder GitHub-Pages-Veröffentlichung aktuell korrekt aktiv sind.

| Möglichkeit | Datenverarbeitung und Wartung | Aufwand/Kostenentscheidung |
|---|---|---|
| PHP/MySQL-Webhosting bei STRATO oder kompatiblem Host | Gesamte Anwendung bei einem Host, gleiche Origin, vorhandene Implementierung direkt nutzbar | Geeignetes kostenpflichtiges Webhosting statt reinem Domainprodukt nötig, falls es nicht bereits gebucht ist; konkretes Angebot erst prüfen |
| GitHub Pages + separates Backend mit DB | Statische Oberfläche bleibt auf Pages; privates Backend verarbeitet Codes und Teilnehmer | Backendhost, Datenschutzvertrag, sichere Sitzung/CORS, Domain-/Proxykonzept und zusätzliche Wartung nötig; konkreter Anbieter und Tarif noch offen |

GitHub Pages ist laut offizieller Dokumentation statisches Hosting. PHP wird dort nicht serverseitig ausgeführt. Die vorhandene PHP-Anwendung kann daher nicht durch bloßes Pushen oder Verschieben in den Pages-Zweig online funktionieren. Es wurde bewusst kein scheinbar funktionierendes öffentliches Anmeldeformular ohne Backend veröffentlicht.

Die aktuelle Implementierung und ihre JSON-API unterstützen denselben Ursprung für Oberfläche und Backend. Die Aufteilung auf GitHub Pages und einen anderen Host wäre ein eigener Architektur-/Deployment-Schritt. Nicht einfach CORS mit * freischalten und Sitzungscookies übertragen.

## Vor dem nächsten Deployment nachweisen

1. Tatsächlicher Tarif und darin enthaltene PHP-Ausführung, unterstützte PHP-Versionen, PDO MySQL, intl, mbstring, sodium.
2. Private MySQL-/MariaDB-Datenbank, InnoDB, erzwungene CHECK-Constraints, Speicherlimits und Backup-/Restorezugriff.
3. Dokumentwurzel frei auf public/ setzbar, Dateien daneben nicht öffentlich, HTTPS-Zertifikat und Redirect, korrekte HTTPS-Signalisierung.
4. Uploadweg (z.B. SFTP), tägliche CLI-/Auftragsplanung für Wartung und Backups, gesicherte Ablage und Restoretest.
5. Hosting-/Datenschutzvertrag, Protokollierungs- und Löschfristen, veröffentlichbare Betreiberangaben.

Keine Tarifbuchung, kein DNS-Wechsel und kein Deployment wurden ausgeführt. Konkrete Preise und Leistungszusagen wurden nicht unterstellt.

Offizielle Quellen, am 5. November 2026 eingesehen:
- https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages
- https://www.strato.de/hosting/
