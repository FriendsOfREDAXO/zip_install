# REDAXO Add-On upload via zip & url

Mit diesem AddOn kannst du gezippte AddOns oder Plugins einfach im Backend oder über die URL zu einer ZIP-Datei hochladen.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/screen.png)

Benutzung
------------
Das AddOn registriert zwei neue Subpages im Installer (neben "Eigene hochladen"). Dort kannst du

 * eine ZIP-Datei eines gültigen AddOns uploaden (wird geprüft).
 * eine URL zu einer ZIP-Datei eines gültigen AddOns angeben.
 * eine URL zu einem GitHub-Repo z.B.: `https://github.com/FriendsOfREDAXO/quick_navigation` (aktueller Haupt-Branch wird geladen)
 * ZIP einer GitHub-Branch laden z.B:  `https://github.com/FriendsOfREDAXO/quick_navigation/tree/dev`

Plugins lassen sich auch installieren. Diese werden automatisch in das richtige AddOn kopiert. (Benennung erfolgt ebenfalls automatisch)

Installation
------------
* Release herunterladen und entpacken.
* Ordner umbenennen in `zip_install`.
* In den Addons-Ordner legen: `/redaxo/src/addons`.

Danach musst du diese nervigen Schritte nie wieder wiederholen, wenn du eigene AddOns/Plugins z.B. von Github installieren möchtest. (oder eigene, lokal entwickelte).

Selbstverständlich kannst Du weiter den REDAXO-Installer nutzen!

Hinweise
------------
Dieses AddOn entpackt REDAXO Plugins/AddOns und verschiebt diese ins korrekte Verzeichnis. Es wird kein Install oder Re-Install durchgeführt. Es werden keine Abhängigkiten beachtet. Bereits existierende Dateien im AddOn/Plugin-Verzeichnis werden überschrieben (wie beim REDAXO-Installer). Die update.php des AddOns wird nicht aufgerufen. Dieses Tool ist mehr als "Github-Release-Upload-Schnell-Mal-Hochschieben" Utility gedacht. Und dafür macht das AddOn seine Arbeit wirklich gut :)

Voraussetzungen
------------

* fileinfo extension
* zlib extension

Changelog
------------
Siehe GitHub- oder REDAXO-Installer Release-Notes

Known Issues
------------
Vor Version 1.0.0-RC2 gab es ein Problem mit Benutzerrechten. Dadurch war kein Update oder De-Install unter gewissen Umständen möglich. Bitte einfach manuell das AddOn per FTP hochladen (ohne den tmp Ordner). Danach auf re-installieren klicken, damit die Permissions des Verzeichnisses korrigiert werden. Anschließend kann wieder über den Installer geupdated werden.
