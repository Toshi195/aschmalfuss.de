# Startseitenlink und Übertragung zu GitHub

Die Repository-Startseite index.html verlinkt auf schweinekopfessen/vorschau/. Diese statische Vorschau läuft auf GitHub Pages. Es werden keine Anmeldungen gespeichert und keine Backendaufrufe ausgeführt. Sämtliche Formularfelder sind deaktiviert, Sitzungstoken und Adminlinks entfernt. Die echte PHP-Anwendung bleibt unter schweinekopfessen/public/ für ein geeignetes PHP-/MySQL-Hosting vorbereitet.

Vorschau aktualisieren: im Projektordner mit PHP `php tools/export-preview.php` ausführen. Das Werkzeug verwendet ausschließlich config.example.php. Danach die neu erzeugten Dateien unter vorschau/ mit übertragen.

## Weitere Änderungen übertragen

Terminal im Repository aschmalfuss.de öffnen:

```powershell
git status
git add index.html README.md schweinekopfessen
git diff --cached --stat
git commit -m "Webseite aktualisieren"
git push origin main
```

Vor Commit kontrollieren, dass keine config.php, Einwohnerdateien, Backup- oder Testdaten enthalten sind. .gitignore schließt die vorgesehenen privaten Pfade aus. Bei einem abgewiesenen Push zuerst `git fetch origin` ausführen und den Unterschied prüfen; niemals mit force einen anderen Stand überschreiben.

Wenn GitHub Pages aus main und dem Stammverzeichnis veröffentlicht, wird der neue Link nach dem erfolgreichen Pages-Build sichtbar. Falls nicht, unter GitHub → Repository → Settings → Pages die Veröffentlichungsquelle und unter Actions den Buildstatus prüfen. Ein erfolgreicher Git-Push allein bestätigt noch keine erfolgreiche Pages-Veröffentlichung.

Öffentliche Adressen bei korrekt aktiver Domain:
- https://aschmalfuss.de/
- https://aschmalfuss.de/schweinekopfessen/vorschau/

GitHub Pages kann kein PHP ausführen: https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages
