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
    public static function validateAndExtractUpload($url = "")
    {
      dump($url);
        if (!empty($url))
        {

            $error = true;
            $tmp_path = rex_path::addon('zip_install');
            $parentIsMissing = false;

            /*$upload = URLUpload::factory('tmp', $tmp_path);
            $upload->file(file_get_contents($url));

            //set allowed mime types
            $upload->set_allowed_mime_types(array('application/zip', 'application/octet-stream', 'multipart/x-zip', 'application/x-zip-compressed'));
            $upload->set_filename('tmp_addon.zip');
            $results = $upload->upload();
            */
            $ch = curl_init();
            $source = $url;
            curl_setopt($ch, CURLOPT_URL, $source);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec ($ch);
            curl_close ($ch);

            $destination = "tmp_addon.zip";
            $file = fopen($destination, "w+");
            fputs($file, $data);
            fclose($file);
            $results['full_path'] = $destination;

            // check if upload status was successful
            if ($results)
            {
                $error = false;
                $isPlugin = false;
                $foldername = '';
                $packageFile = false;
                $zippy = Zippy::load();

                try {
                    $archive = $zippy->open($results['full_path']);

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
                @unlink($results['full_path']);
            }


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
