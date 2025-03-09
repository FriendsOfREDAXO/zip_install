# REDAXO AddOn-Installation via ZIP & GitHub

Dieses AddOn ermöglicht die einfache Installation von AddOns oder Plugins durch Hochladen von ZIP-Dateien, Installation über eine URL oder direkt von GitHub.

![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/FriendsOfREDAXO/zip_install?labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit%20Reviews)

**WICHTIGER HINWEIS: Dieses AddOn ist ausschließlich für erfahrene Systemadministrator:innen bestimmt. Die unsachgemäße Anwendung kann zu unerwartetem Verhalten oder Schäden führen.**

**Will man ein vorhandenes AddOn ersetzen, sollte dieses für eine saubere Installation vorher deinstalliert werden, sonst bleiben evtl. alte Dateien erhalten**

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/screenshot.png)

## Funktionen

*   ZIP-Upload über den Browser
*   Installation über eine URL, die direkt zu einer ZIP-Datei führt
*   GitHub-Integration:
    *   Installation direkt von GitHub-Repositories
    *   Repository-Suche nach Benutzer/Organisation
    *   Anzeige von Beschreibungen und Details der Repositories

## Installation

*   Download und Installation über den Installer oder
*   Download der ZIP-Datei
*   Entpacken in den AddOns-Ordner als `/redaxo/src/addons/zip_install/`
*   Installation und Aktivierung in REDAXO

## Verwendung

Das AddOn ist im Installer unter "ZIP Upload/GitHub" zu finden und bietet drei Möglichkeiten zur Installation:

1.  **ZIP-Upload**: Direkter Upload einer ZIP-Datei eines AddOns/Plugins
2.  **URL-Installation**: Eingabe eines Links zu einer ZIP-Datei oder einem GitHub-Repository
3.  **GitHub-Integration**: Suche nach GitHub-Repositories und direkte Installation

### GitHub-URLs

Folgende GitHub-URL-Formate werden unterstützt:

*   Repository-URL: `https://github.com/FriendsOfREDAXO/demo_base`
*   Spezifischer Branch: `https://github.com/FriendsOfREDAXO/demo_base/tree/main`

### Plugins

Plugins werden automatisch in das entsprechende Verzeichnis des zugehörigen AddOns installiert. Der Name wird dabei automatisch aus der `package.yml` übernommen.

## Wichtige Hinweise

*   Das AddOn überschreibt vorhandene Dateien ohne Rückfrage.
*   Es wird keine Installation oder Neuinstallation durchgeführt.
*   Abhängigkeiten werden nicht geprüft.
*   Die `update.php` des AddOns wird nicht ausgeführt.
*   Der Upload ist auf 50 MB begrenzt.

## GitHub API-Token setzen

Das Add-on liefert keinen Token für die GitHub-API mit. Ohne Token sind die API-Abfragen auf 60 pro Stunde begrenzt.
Ein persönlicher Zugriffstoken kann unter GitHub > Settings > Developer settings > Personal access tokens erstellt werden.
Der Token benötigt mindestens 'public_repo' Berechtigung für öffentliche Repositories.
Der Token kann z.B. in der install.php des project-Add-ons oder einem eigenen wie folgt updatesicher gesetzt werden:

```
$addon = rex_addon::get('zip_install');
$addon->setConfig('github_token', 'GitHubToken');
```

## Voraussetzungen

*   REDAXO >= 5.18
*   PHP >= 8.1
*   PHP-Erweiterungen: zip, fileinfo

## API Dokumentation für die `ZipInstall` Klasse

Diese Dokumentation beschreibt die `ZipInstall` Klasse, die zum Installieren von REDAXO Addons und Plugins aus ZIP-Archiven verwendet wird. Die Klasse bietet Funktionen zum Hochladen von ZIP-Dateien, zum Herunterladen von ZIP-Dateien von URLs (inkl. GitHub), sowie zum Extrahieren und Installieren der Addons/Plugins.

**Klassenname:** `ZipInstall`

**Namespace:** `FriendsOfRedaxo\ZipInstall`

### Konstruktor

```php
public function __construct()
```

**Beschreibung:**

Initialisiert die `ZipInstall` Klasse. Erstellt einen temporären Ordner im Cache-Verzeichnis des Addons, falls dieser nicht existiert.

**Parameter:**

*   Keine

**Rückgabewert:**

*   Keiner

### Methoden

#### `handleFileUpload()`

```php
public function handleFileUpload(): string
```

**Beschreibung:**

Verarbeitet den Upload einer ZIP-Datei, die über ein HTML-Formular hochgeladen wurde.

**Parameter:**

*   Keine

**Rückgabewert:**

*   `string`: Gibt einen HTML-String für eine Erfolgs- oder Fehlermeldung zurück.

**Funktionsweise:**

1.  Prüft, ob eine Datei über `$_FILES['zip_file']` hochgeladen wurde.
2.  Überprüft den MIME-Type der hochgeladenen Datei (erlaubt sind `application/zip` und `application/octet-stream`).
3.  Überprüft die Dateigröße anhand der Konfigurationseinstellung `upload_max_size`.
4.  Verschiebt die hochgeladene Datei in den temporären Ordner mit einem eindeutigen Dateinamen.
5.  Ruft die Methode `installZip()` auf, um die Installation durchzuführen.

**Fehlermeldungen:**

*   `zip_install_upload_failed`: Upload fehlgeschlagen.
*   `zip_install_mime_error`: Ungültiger Dateityp. Bitte laden Sie eine ZIP-Datei hoch.
*   `zip_install_size_error`: Die Dateigröße überschreitet das Limit von `%%size%%` MB.

#### `handleUrlInput()`

```php
public function handleUrlInput(string $url): string
```

**Beschreibung:**

Verarbeitet eine URL, die auf eine ZIP-Datei oder ein GitHub-Repository verweist.

**Parameter:**

*   `$url` (`string`): Die URL, die verarbeitet werden soll.

**Rückgabewert:**

*   `string`: Gibt einen HTML-String für eine Erfolgs- oder Fehlermeldung zurück.

**Funktionsweise:**

1.  Überprüft, ob die URL nicht leer ist.
2.  Entfernt den abschließenden Slash von der URL.
3.  Prüft, ob die URL ein GitHub-Repository ist und generiert die Download-URL der ZIP-Datei.
4.  Lädt die ZIP-Datei in den temporären Ordner mit einem eindeutigen Dateinamen herunter.
5.  Ruft die Methode `installZip()` auf, um die Installation durchzuführen.

**Fehlermeldungen:**

*   `zip_install_invalid_url`: Ungültige URL.
*   `zip_install_url_file_not_loaded`: Die Datei konnte von der angegebenen URL nicht geladen werden.

#### `installZip()`

```php
protected function installZip(string $tmpFile): string
```

**Beschreibung:**

Extrahiert und installiert ein Addon oder Plugin aus einer temporären ZIP-Datei.

**Parameter:**

*   `$tmpFile` (`string`): Der Pfad zur temporären ZIP-Datei.

**Rückgabewert:**

*   `string`: Gibt einen HTML-String für eine Erfolgs- oder Fehlermeldung zurück.

**Funktionsweise:**

1.  Öffnet die ZIP-Datei mit der `ZipArchive`-Klasse.
2.  Sucht die `package.yml` Datei im ZIP-Archiv.
3.  Extrahiert den Inhalt des ZIP-Archivs in einen temporären Ordner.
4.  Liest die `package.yml` Datei, um die Addon-/Plugin-Informationen zu erhalten.
5.  Kopiert die Dateien an den entsprechenden Speicherort im REDAXO-System.
6.  Löscht den temporären Ordner und die temporäre ZIP-Datei.

**Fehlermeldungen:**

*   `zip_install_invalid_addon`: Das Addon/Plugin ist ungültig oder konnte nicht installiert werden.
*   `zip_install_plugin_parent_missing`: Das Parent-Addon für dieses Plugin ist nicht vorhanden.

#### `getGitHubRepos()`

```php
public function getGitHubRepos(string $username): array
```

**Beschreibung:**

Holt eine Liste von GitHub-Repositories für einen bestimmten Benutzer oder eine Organisation.

**Parameter:**

*   `$username` (`string`): Der GitHub-Benutzername oder Name der Organisation.

**Rückgabewert:**

*   `array<int, array{name: string, description: ?string, url: string, download_url: string, default_branch: string}>`: Gibt ein Array von GitHub-Repositories zurück. Jedes Repository enthält Name, Beschreibung, URL, Download-URL und den Default-Branch.

**Funktionsweise:**

1.  Erstellt eine API-Anfrage an GitHub für die Repositories.
2.  Filtert Fork, archivierte und deaktivierte Repositories heraus.
3.  Formatiert die Repositories in ein einfach zu handhabendes Array.

#### `isValidUrl()`

```php
protected function isValidUrl(string $url): bool
```

**Beschreibung:**

Überprüft, ob eine URL gültig und erreichbar ist.

**Parameter:**

*   `$url` (`string`): Die zu überprüfende URL.

**Rückgabewert:**

*   `bool`: Gibt `true` zurück, wenn die URL gültig und erreichbar ist, sonst `false`.

**Funktionsweise:**

1.  Führt eine `get_headers()` Anfrage durch.
2.  Überprüft, ob der Statuscode `200` enthalten ist.

#### `downloadFile()`

```php
protected function downloadFile(string $url, string $destination): bool
```

**Beschreibung:**

Lädt eine Datei von einer URL herunter und speichert sie auf dem Server.

**Parameter:**

*   `$url` (`string`): Die URL der herunterzuladenden Datei.
*   `$destination` (`string`): Der Dateipfad zum Speichern der heruntergeladenen Datei.

**Rückgabewert:**

*   `bool`: Gibt `true` zurück, wenn die Datei erfolgreich heruntergeladen und gespeichert wurde, sonst `false`.

**Funktionsweise:**

1.  Verwendet `file_get_contents()` um den Inhalt der URL abzurufen.
2.  Speichert den Inhalt in die angegebene Datei mit `rex_file::put()`.

### Zusammenfassung

Die `ZipInstall` Klasse bietet eine umfassende Möglichkeit zur Installation von REDAXO Addons und Plugins per ZIP-Upload oder URL. Sie enthält Sicherheitsvorkehrungen (MIME-Type Überprüfung, eindeutige Dateinamen), um das Risiko von Sicherheitslücken zu minimieren und die Stabilität der Installation zu gewährleisten.



## Lizenz

MIT Lizenz, siehe [LICENSE.md](LICENSE.md)

## Autor

*   [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)

## Lead
[Thomas Skerbis](https://github.com/skerbis)  



## Danksagung

*   Ursprüngliches AddOn von [@aeberhard](https://github.com/aeberhard)
