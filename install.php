<?php

$addon = rex_addon::get('zip_install');

// Cache-Ordner erstellen mit korrekten Berechtigungen
$cacheDir = $addon->getCachePath();
rex_dir::create($cacheDir);

// Temp Upload Ordner erstellen
$tmpDir = $addon->getCachePath('tmp_uploads');
rex_dir::create($tmpDir);

// htaccess zum Schutz des Cache-Verzeichnisses erstellen
$htaccess = $cacheDir . '/.htaccess';
if (!file_exists($htaccess)) {
    $content = "Order deny,allow\nDeny from all";
    rex_file::put($htaccess, $content);
}
