<?php
/**
 * zip_install Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */

use Alchemy\Zippy\Zippy;

class zip_install {
    protected static function installZip($tmp_file) {
        $error = false;
        $isPlugin = false;
        $foldername = '';
        $packageFile = false;
        $zippy = Zippy::load();

        $tmp_path = rex_addon::get('zip_install')->getCachePath('tmp_uploads');

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
                $archive->extract($tmp_path);

                // delete garbage
                rex_dir::delete($tmp_path . '/__MACOSX');
                rex_dir::delete($tmp_path . '/.git');
                rex_dir::delete($tmp_path . '/.gitignore');
                rex_dir::delete($tmp_path . '/thumbs.db');

                $config = rex_file::getConfig($tmp_path . '/' . $foldername . 'package.yml');
                if($config['package'])
                {
                    $pluginCheck = explode('/', $config['package']);

                    if(count($pluginCheck) > 1) {
                        $isPlugin = true;
                        // check if parent exists
                        if(rex_dir::isWritable(rex_path::addon($pluginCheck[0])))
                        {
                            // its a plugin, it should have a parent
                            if(!rex_dir::copy($tmp_path . '/' . $foldername, rex_path::addon($pluginCheck[0], 'plugins/'.$pluginCheck[1])))
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
                        if(!rex_dir::copy($tmp_path . '/' . $foldername, rex_path::addon($config['package'])))
                        {
                            $error = true;
                        }
                    }

                    rex_dir::delete($tmp_path . '/' . $foldername);
                }

            }
            else
            {
                $error = true;
            }

        } catch (Exception $e) {
            echo rex_view::warning($e->getMessage());
            $error = true;
        }

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
            if(isset($parentIsMissing) && $parentIsMissing)
            {
                echo rex_view::warning(rex_i18n::rawMsg('zip_install_plugin_parent_missing'));
            }
        }
    }
}
