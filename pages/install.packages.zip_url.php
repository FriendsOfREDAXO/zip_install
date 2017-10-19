<?php

/**
* zip_install Addon.
*
* @author Friends Of REDAXO
*
* @var rex_addon
*/

zip_url::validateAndExtractUpload();

$content = '';

$content .= '
<form name="upload_zip_file" method="POST" action="" enctype="multipart/form-data">
  <div class="form-group">
    <label for="zip">'.rex_i18n::rawMsg('zip_install_url').'</label>
    <small>'.rex_i18n::rawMsg('zip_install_url_wrappers').': '. implode(', ', zip_url::getAvailableWrappers()).'</small>
    <!-- https://github.com/FriendsOfREDAXO/ui_tools/archive/develop.zip -->
    <input type="text" class="form-control" name="file_url" id="zip" placeholder="https://github.com/FriendsOfREDAXO/adminer/archive/master.zip">
    <p class="help-block">'.rex_i18n::rawMsg('zip_install_url_choose_info').'</p>
    
  </div>
  <button type="submit" class="btn btn-default">'.rex_i18n::rawMsg('zip_install_url_title').'</button>
</form>
';




$fragment = new rex_fragment();

$fragment->setVar('title', rex_i18n::rawMsg('zip_install_url_title'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
