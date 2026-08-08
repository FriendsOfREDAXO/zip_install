<?php

$addon = rex_addon::get('zip_install');

// Add CSS for better GitHub repo display
if (rex::isBackend() && rex_be_controller::getCurrentPage() === 'install/zip_install/upload') {
    rex_view::addCssFile($addon->getAssetsUrl('css/zip_install.css?v=' . $addon->getVersion()));
}
