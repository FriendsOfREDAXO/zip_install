# REDAXO AddOn-Installation via ZIP & GitHub

Mit diesem AddOn kannst du AddOns oder Plugins einfach als ZIP-Datei hochladen, über eine URL installieren oder direkt von GitHub laden.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/zip2.png)

## Features

* ZIP-Upload aus dem Browser
* Installation via URL direkt zu einer ZIP-Datei
* GitHub-Integration:
    * Installation direkt von GitHub-Repositories
    * Repository-Suche nach Benutzer/Organisation
    * Anzeige von Beschreibungen und Details zu den Repositories

## Installation

* Über den Installer herunterladen und installieren oder
* ZIP-Datei herunterladen
* In den AddOns-Ordner entpacken als `/redaxo/src/addons/zip_install/`
* In REDAXO installieren und aktivieren

## Benutzung

Das AddOn ist im Installer unter "ZIP Upload/GitHub" zu finden und bietet drei Möglichkeiten zur Installation:

1. **ZIP-Upload**: ZIP-Datei eines AddOns/Plugins direkt hochladen
2. **URL-Installation**: Link zu einer ZIP-Datei oder einem GitHub-Repository eingeben
3. **GitHub-Integration**: Nach GitHub-Repositories suchen und direkt installieren

### GitHub URLs

Folgende GitHub-URL-Formate werden unterstützt:

* Repository-URL: `https://github.com/FriendsOfREDAXO/demo_base`
* Spezifischer Branch: `https://github.com/FriendsOfREDAXO/demo_base/tree/main`

### Plugins

Plugins werden automatisch in das richtige Verzeichnis des zugehörigen AddOns installiert. Der Name wird dabei automatisch aus der package.yml übernommen.

## Hinweise

* Das AddOn überschreibt existierende Dateien ohne Rückfrage
* Es wird kein Install oder Re-Install durchgeführt 
* Abhängigkeiten werden nicht geprüft
* Die update.php des AddOns wird nicht ausgeführt
* Der Upload ist auf 20 MB begrenzt (kann in den Einstellungen angepasst werden)

## Voraussetzungen

* REDAXO >= 5.18
* PHP >= 8.1
* PHP-Extensions: zip, fileinfo

## Lizenz

MIT Lizenz, siehe [LICENSE.md](LICENSE.md)

## Autor

* [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)

## Credits

* Ursprüngliches AddOn von [@aeberhard](https://github.com/aeberhard)
* GitHub-Integration inspiriert von [@skerbis](https://github.com/skerbis)
* Weiterentwicklung durch die [Friends Of REDAXO](https://github.com/FriendsOfREDAXO)
