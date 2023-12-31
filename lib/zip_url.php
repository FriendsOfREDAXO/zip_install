<?php
/**
 * zip_install Addon.
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
        $fileUrl = rex_post('file_url');

        if (!empty($fileUrl))
        {
            $tmp_file = rex_path::addon('zip_install', 'tmp/._tmp.zip');
            $isGithubRepo = preg_match("/^https:\/\/github\.com\/[\w-]+\/[\w-]+$/", $fileUrl);

            if ($isGithubRepo) {
                $mainBranchUrl = $fileUrl . '/archive/main.zip';
                $masterBranchUrl = $fileUrl . '/archive/master.zip';

                // Versuche zuerst den 'main'-Branch
                if (self::downloadFile($mainBranchUrl, $tmp_file) && file_exists($tmp_file)) {
                    self::installZip($tmp_file);
                    return;
                }

                // Versuche dann den 'master'-Branch
                if (self::downloadFile($masterBranchUrl, $tmp_file) && file_exists($tmp_file)) {
                    self::installZip($tmp_file);
                    return;
                }
            } else {
                // Direkter Download der ZIP-Datei von der angegebenen URL
                if (self::downloadFile($fileUrl, $tmp_file) && file_exists($tmp_file)) {
                    self::installZip($tmp_file);
                    return;
                }
            }

            echo rex_view::error(rex_i18n::rawMsg('zip_install_url_file_not_loaded'));
        }
    }
}
