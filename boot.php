<?php

// Add CSS for better GitHub repo display
if (rex::isBackend() && is_string(rex_get('page', 'string')) && rex_be_controller::getCurrentPage() === 'install/packages/zip_install') {
    rex_view::addCssFile($this->getAssetsUrl('css/zip_install.css'));
}
