# REDAXO ZIP-Upload AddOn und Plugin Install!
Mit diesem AddOn kannst du gezippte AddOns oder Plugins einfach im Backend hochladen und installieren.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/zip_install/assets/screenshot.png)

Benutzung
------------
Das AddOn registriert eine neue Subpage im Installer (neben "Eigene hochladen"). Dort kannst du einfach eine ZIP-Datei eines gültigen AddOns uploaden (wird geprüft). Plugins lassen sich auch installieren. Diese werden automatisch in das richtige AddOn kopiert. (Benennung erfolgt ebenfalls automatisch)

Installation
------------
Hinweis: dies ist kein Plugin! (verhält sich jedoch wie eines)

* Release herunterladen und entpacken.
* Ordner umbenennen in `zip_install`.
* In den Addons-Ordner legen: `/redaxo/src/addons`.

Danach musst du diese nervigen Schritte nie wieder wiederholen, wenn du eigene AddOns/Plugins z.B. von Github installieren möchtest. (oder eigene, lokal entwickelte).

Oder den REDAXO-Installer nutzen!

Voraussetzungen
------------

* fileinfo extension
* zlib extension
