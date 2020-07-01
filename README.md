# REDAXO ZIP-Upload AddOn und Plugin Install!
Mit diesem AddOn kannst du gezippte AddOns oder Plugins einfach im Backend oder über die URL zu einer ZIP-Datei hochladen.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/screen.png)
![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/screen_zip.png)

Benutzung
------------
Das AddOn registriert zwei neue Subpages im Installer (neben "Eigene hochladen"). Dort kannst du

 * eine ZIP-Datei eines gültigen AddOns uploaden (wird geprüft).
 * eine URL zu einer ZIP-Datei eines gültigen AddOns angeben.

Plugins lassen sich auch installieren. Diese werden automatisch in das richtige AddOn kopiert. (Benennung erfolgt ebenfalls automatisch)

Installation
------------
Hinweis: dies ist kein Plugin! (verhält sich jedoch wie eines)

* Release herunterladen und entpacken.
* Ordner umbenennen in `zip_install`.
* In den Addons-Ordner legen: `/redaxo/src/addons`.

Danach musst du diese nervigen Schritte nie wieder wiederholen, wenn du eigene AddOns/Plugins z.B. von Github installieren möchtest. (oder eigene, lokal entwickelte).

Oder den REDAXO-Installer nutzen!

Hinweise
------------
Dieses AddOn entpackt REDAXO Plugins/AddOns und verschiebt diese ins korrekte Verzeichnis. Es wird kein Install oder Re-Install durchgeführt. Es werden keine Abhängigkiten beachtet. Bereits existierende Dateien im AddOn/Plugin-Verzeichnis werden überschrieben (wie beim REDAXO-Installer). Die update.php des AddOns wird nicht aufgerufen. Dieses Tool ist mehr als "Github-Release-Upload-Schnell-Mal-Hochschieben" Utility gedacht. Und dafür macht das AddOn seine Arbeit wirklich gut :)

Voraussetzungen
------------

* fileinfo extension
* zlib extension

Changelog
------------
 * Bug #1 gefixed
 * Version 1.1 URL install

Known Issues
------------
Vor Version 1.0.0-RC2 gab es ein Problem mit Benutzerrechten. Dadurch war kein Update oder De-Install unter gewissen Umständen möglich. Bitte einfach manuell das AddOn per FTP hochladen (ohne den tmp Ordner). Danach auf re-installieren klicken, damit die Permissions des Verzeichnisses korrigiert werden. Anschließend kann wieder über den Installer geupdated werden.
