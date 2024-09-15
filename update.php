<?php
$tmpPath = rex_path::addon('zip_install', 'tmp');

if (is_dir($tmpPath)) {
    // Versuche, das Verzeichnis zu löschen
    if (!rex_dir::delete($tmpPath)) {
        // Wenn das Löschen fehlschlägt, gib eine Warnung aus
        rex_logger::logWarning('Das temporäre Verzeichnis ' . $tmpPath . ' konnte nicht gelöscht werden.');
    }
}
