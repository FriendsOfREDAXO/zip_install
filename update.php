<?php
$addon = rex_addon::get('zip_install');

if (rex_version::compare($addon->getVersion(), '1.5', '<')) {
    $tmpPath = rex_path::addon('zip_install', 'tmp');
    
    if (is_dir($tmpPath)) {
        // Try to delete the directory
        if (!rex_dir::delete($tmpPath)) {
            // If deletion fails, log a warning
            rex_logger::logWarning('The temporary directory ' . $tmpPath . ' could not be deleted.');
        } else {
            rex_logger::logInfo('The temporary directory ' . $tmpPath . ' has been successfully deleted.');
        }
    }
}
