<?php
/**
 * media_manager_autorewrite Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */


class zip_url extends zip_install
{
    
    public static function getAvailableWrappers() {
        $wrappers = stream_get_wrappers();
        return array_intersect(['https','http','ftp','ftps','ssh', 'data', 'glob'], $wrappers);
    }

    protected static function isWrapperAvailable($w)
    {
        return in_array($w, self::getAvailableWrappers());
    }
    
    public static function validateAndExtractUpload($url = "")
    {
        $url = rex_post('file_url');
        if (!empty($url))
        {
            
            $tmp_file = rex_path::addon('zip_install', 'tmp/._.zip');
        
            $parentIsMissing = false;

            $wrapper = strstr($url, ':', true);
            
            if (!self::isWrapperAvailable($wrapper)) {
                echo rex_view::warning(rex_i18n::rawMsg('zip_install_url_no_wrapper'));
                return;
            }
            
            $contents = file_get_contents($url);
            if ($contents === false) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_file_not_loaded'));
                return;
            }
            
            // Check Magic Bytes for ZIP File
            if (substr($contents, 0, 2) !== 'PK') {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_no_zip'));
                return;
            }
            
            if (file_put_contents($tmp_file, $contents) === false) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_tmp_not_written'));
                return;
            }
            
            if (!file_exists($tmp_file)) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_tmp_not_written'));
                return;
            }
            
            self::installZip($tmp_file);
        }
    }

}
