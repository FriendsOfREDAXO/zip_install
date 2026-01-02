<?php

$package = rex_addon::get('zip_install');

echo rex_view::title($package->i18n('title'));

rex_be_controller::includeCurrentPageSubPath();
