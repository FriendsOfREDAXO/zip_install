<?php
/**
 * zip_install Addon.
 * 
 *
 * @author Friends Of REDAXO
 * @author stefan-beyer
 *
 * @var rex_addon
 */


class zip_url extends zip_install
{
    
    protected static function downloadFile($url, $destination)
    {
        try {
            $socket = rex_socket::factoryURL($url);
            $response = $socket->doGet();
            // handle redirects (limited)
            $redircount = 0;
            while ($response->isRedirection() && $redircount < 3) {
                $location = $response->getHeader('Location');

                if (empty($location)) {
                    return false;
                }
                

                # add host if needed
                if (strpos($location, '//') === false) {
                    $parsed = parse_url($url);
                    $host = $parsed['scheme'] . '://' . $parsed['host'];
                    if (isset($parsed['port'])) {
                        $host .= ':'. $parsed['port'];
                    }
                    $location = $host . $location;
                }

                $socket = rex_socket::factoryURL($location);
                $response = $socket->doGet();
                $redircount++;
            }

            if(!$response->isOk()) {
                return false;
            }

            // write to file
            if ($response->writeBodyTo($destination)) {
                return true;
            }
        } catch(rex_socket_exception $e) {}
        return false;
    }


    public static function validateAndExtractUpload()
    {
        $url = rex_post('file_url');
        if (!empty($url))
        {
            $tmp_file = rex_path::addon('zip_install', 'tmp/._tmp.zip');
        
            if (!self::downloadFile($url, $tmp_file)) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_file_not_loaded'));
            }
            
            if (!file_exists($tmp_file)) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_url_tmp_not_written'));
                return;
            }
            
            self::installZip($tmp_file);
        }
    }

}
