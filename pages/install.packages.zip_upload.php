<?php

/**
* zip_install Addon.
*
* @author Friends Of REDAXO
*
* @var rex_addon
*/

zip_upload::validateAndExtractUpload();

$content = '';

$content .= '
<form name="upload_zip_file" method="POST" action="" enctype="multipart/form-data">
  <div class="form-group">
    <label for="zip">'.rex_i18n::rawMsg('zip_install_choose_file').'</label>
    <input type="file" name="file" id="zip" accept=".zip">
    <p class="help-block">'.rex_i18n::rawMsg('zip_install_choose_info').'</p>
  </div>
  <button type="submit" class="btn btn-default">'.rex_i18n::rawMsg('zip_install_upload_file').'</button>
</form>
';



$fragment = new rex_fragment();

$fragment->setVar('title', rex_i18n::rawMsg('zip_install_title'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
