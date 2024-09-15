<?php
$addon = rex_addon::get('zip_install');

// Check if the installed version is less than 1.5
if (rex_version::compare($addon->getVersion(), '1.5', '<')) {
    $tmpPath = rex_path::addon('zip_install', 'tmp');
    
    if (is_dir($tmpPath)) {
        // Try to delete the directory
        if (!rex_dir::delete($tmpPath)) {
            // If deletion fails, log a warning
            rex_logger::log('The temporary directory ' . $tmpPath . ' could not be deleted.', null, rex_logger::WARNING);
        } else {
            // Log successful deletion as a notice
            rex_logger::log('The temporary directory ' . $tmpPath . ' has been successfully deleted.', null, rex_logger::NOTICE);
        }
    }
}
