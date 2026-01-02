<?php

// Add CSS for better GitHub repo display
if (rex::isBackend() && rex_be_controller::getCurrentPage() === 'install/zip_install/upload') {
    rex_view::addCssFile($this->getAssetsUrl('css/zip_install.css?v=' . $this->getVersion()));
}
