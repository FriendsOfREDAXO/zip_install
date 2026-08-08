# Changelog

Alle nennenswerten Änderungen am zip_install AddOn werden hier dokumentiert.

## [2.4.2] - 2026-08-08

### Verbesserungen
- Die Bereinigung nach ZIP-Entpacken folgt jetzt exakt derselben Default-Liste wie in der installer-action (REDAXO-/Finder-Ignore + Install-Ignore).
- Zusätzlich werden nun Einträge aus `installer_ignore` der entpackten `package.yml` berücksichtigt (sofern gesetzt), analog zum Verhalten der installer-action.
- Der Bereinigungsbericht im Backend zeigt weiterhin transparent an, welche Einträge entfernt wurden.
- Tests für die `installer_ignore`-Patternlogik ergänzt (`tests/installer_ignore_match_test.php`).
- Das gesamte AddOn wurde mit RexStan geprüft und typisiert; dabei wurden unter anderem Konfigurations-, cURL-, Fileinfo-, ZIP- und Logging-Randfälle abgesichert.

## [2.4.1] - 2026-08-08

### Verbesserungen
- ZIP-Import bereinigt entpackte Pakete vor dem Kopieren um Git-Metadaten (`.git`, `.github`, `.gitlab`), große Build-Abhängigkeiten (`node_modules`) sowie zugehörige Git-Dateien (`.gitignore`, `.gitattributes`, `.gitmodules`).
- Nach Entpacken wird ein Bereinigungsbericht im Backend ausgegeben (inkl. Liste der entfernten Einträge oder expliziter Hinweis, wenn nichts entfernt wurde).
- Stabilerer Installations-Workflow nach Upload: Dateisystem-Synchronisierung wird auch im Upload-Pfad ausgeführt, damit neu entpackte AddOns sofort in der Paketverwaltung erkannt werden.
- Plugin-Pakete (`parent/plugin`) werden für Erkennung und Install-Link robust auf den Parent-AddOn-Key aufgelöst.
