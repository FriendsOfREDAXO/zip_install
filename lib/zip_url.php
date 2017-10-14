<?php
/**
 * media_manager_autorewrite Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */

use Alchemy\Zippy\Zippy;

class zip_url
{
    
    protected static function isWrapperAvailable($w)
    {
        $wrappers = stream_get_wrappers();
        return in_array($w, $wrappers);
    }
    
    public static function validateAndExtractUpload($url = "")
    {
        $url = rex_post('file_url');
        if (!empty($url))
        {
            $error = true;
            
            $tmp_file = rex_path::addon('zip_install', 'tmp/._.zip');
        
            $parentIsMissing = false;

            $wrapper = strstr($url, ':', true);
            
            if (!self::isWrapperAvailable($wrapper)) {
                echo rex_view::warning(rex_i18n::rawMsg('zip_install_no_wrapper'));
                return;
            }
            
            $contents = file_get_contents($url);
            if ($contents === false) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_file_not_loaded'));
                return;
            }
            
            // Check Magic Bytes for ZIP File
            if (substr($contents, 0, 2) !== 'PK') {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_no_zip'));
                return;
            }
            
            if (file_put_contents($tmp_file, $contents) === false) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_tmp_not_written'));
                return;
            }
            
            if (!file_exists($tmp_file)) {
                echo rex_view::error(rex_i18n::rawMsg('zip_install_tmp_not_written'));
                return;
            }
            
            $error = false;
            $isPlugin = false;
            $foldername = '';
            $packageFile = false;
            $zippy = Zippy::load();

            try {
                $archive = $zippy->open($tmp_file);

                $i = 1;
                foreach ($archive as $member) {
                    if(!$member->isDir() && $i == 1)
                    {
                        $error = true;
                        break;
                    }
                    else if($member->isDir() && $i == 1)
                    {
                        $foldername = $member->getLocation();
                    }

                    // search for first package.yml
                    if(strpos($member->getLocation(), 'package.yml') && !$packageFile)
                    {
                        $packageFile = $member->getLocation();
                    }

                    $i++;
                }

                if($packageFile && !$error)
                {
                    // extract into tmp folder
                    $archive->extract(rex_path::addon('zip_install','tmp'));

                    // delete garbage
                    rex_dir::delete(rex_path::addon('zip_install','tmp/__MACOSX'));
                    rex_dir::delete(rex_path::addon('zip_install','tmp/.git'));
                    rex_dir::delete(rex_path::addon('zip_install','tmp/.gitignore'));
                    rex_dir::delete(rex_path::addon('zip_install','tmp/thumbs.db'));


                    $config = rex_file::getConfig(rex_path::addon('zip_install','tmp/'.$foldername).'package.yml');
                    if($config['package'])
                    {
                        $pluginCheck = explode('/', $config['package']);

                        if(count($pluginCheck) > 1) {
                            $isPlugin = true;
                            // check if parent exists
                            if(rex_dir::isWritable(rex_path::addon($pluginCheck[0])))
                            {
                                // its a plugin, it should have a parent
                                if(!rex_dir::copy(rex_path::addon('zip_install','tmp/'.$foldername), rex_path::addon($pluginCheck[0], 'plugins/'.$pluginCheck[1])))
                                {
                                    $error = true;
                                }
                            }
                            else
                            {
                                $parentIsMissing = true;
                                $error = true;
                            }

                        }
                        else
                        {
                            // its an addon
                            // copy over, no matter what!
                            if(!rex_dir::copy(rex_path::addon('zip_install','tmp/'.$foldername), rex_path::addon($config['package'])))
                            {
                                $error = true;
                            }
                        }


                        rex_dir::delete(rex_path::addon('zip_install','tmp/'.$foldername));
                    }

                }
                else
                {
                    $error = true;
                }

            } catch (Exception $e) {}

            // delete tmp uploaded zip-file
            @unlink($tmp_file);
            


            if (!$error)
            {
                if($isPlugin)
                {
                    echo rex_view::info(str_replace('%%plugin%%', $config['package'], rex_i18n::rawMsg('zip_install_plugin_install_succeed')));
                }
                else
                {
                    echo rex_view::info(str_replace('%%addon%%', $config['package'], rex_i18n::rawMsg('zip_install_install_succeed')));
                }
            }
            else
            {
                echo rex_view::warning(rex_i18n::rawMsg('zip_install_invalid_addon'));
                if($parentIsMissing)
                {
                    echo rex_view::warning(rex_i18n::rawMsg('zip_install_plugin_parent_missing'));
                }

            }
        }
    }

}
