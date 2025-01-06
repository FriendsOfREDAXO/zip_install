<?php
$addon = rex_addon::get('zip_install');

// Check if the installed version is less than current
if (rex_string::versionCompare($addon->getVersion(), '2.0.0', '<')) {
    $tmpDir = $addon->getCachePath('tmp_uploads');
    
    if (is_dir($tmpDir)) {
        // Try to delete the directory
        if (!rex_dir::delete($tmpDir)) {
            // If deletion fails, log a warning
            rex_logger::factory()->warning('The temporary directory ' . $tmpDir . ' could not be deleted.');
        } else {
            // Log successful deletion as a notice
            rex_logger::factory()->notice('The temporary directory ' . $tmpDir . ' has been successfully deleted.');
        }
        
        // Create new tmp directory
        rex_dir::create($tmpDir);
    }
}
