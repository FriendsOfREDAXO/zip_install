<?php
/**
 * zip_install Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */

class zip_upload extends zip_install
{
    public static function validateAndExtractUpload()
    {
        if (!empty($_FILES['file']))
        {
            $tmp_path = rex_addon::get('zip_install')->getCachePath('tmp_uploads');
            
            // Ensure the temporary path exists
            if (!file_exists($tmp_path)) {
                rex_dir::create($tmp_path);
            }

            $parentIsMissing = false;

            $upload = Upload::factory('tmp', $tmp_path);
            $upload->file($_FILES['file']);

            //set allowed mime types
            $upload->set_allowed_mime_types(array('application/zip', 'application/octet-stream', 'multipart/x-zip', 'application/x-zip-compressed'));
            $upload->set_filename('tmp_addon.zip');
            $results = $upload->upload();

            // check if upload status was successful
            if ($results['status'] === true)
            {
                self::installZip($results['full_path']);
            }
            else 
            {
                echo rex_view::warning(rex_i18n::rawMsg('zip_install_upload_failed'));
            }
        }
    }
}
