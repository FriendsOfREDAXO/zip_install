# REDAXO AddOn-Installation via ZIP & GitHub

Dieses AddOn ermöglicht die einfache Installation von AddOns oder Plugins durch Hochladen von ZIP-Dateien, Installation über eine URL oder direkt von GitHub.

**WICHTIGER HINWEIS: Dieses AddOn sind ausschließlich für erfahrene Systemadministrator:innen bestimmt. Die unsachgemäße Anwendung kann zu unerwartetem Verhalten oder Schäden führen.**

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/zip2.png)

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
*   Der Upload ist auf 20 MB begrenzt (kann in den Einstellungen angepasst werden).

## Voraussetzungen

*   REDAXO >= 5.18
*   PHP >= 8.1
*   PHP-Erweiterungen: zip, fileinfo

## Lizenz

MIT Lizenz, siehe [LICENSE.md](LICENSE.md)

## Autor

*   [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)

## Danksagung

*   Ursprüngliches AddOn von [@aeberhard](https://github.com/aeberhard)
*   GitHub-Integration inspiriert von [@skerbis](https://github.com/skerbis)
*   Weiterentwicklung durch die [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)
