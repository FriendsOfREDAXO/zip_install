<?php
// lib/ZipInstall.php

namespace FriendsOfRedaxo\ZipInstall;

class ZipInstall {
    protected static function installZip(string $tmpFile): void 
    {
        $error = false;
        $isPlugin = false;
        $foldername = '';
        $packageFile = false;
        
        $addon = \rex_addon::get('zip_install');
        // Nutze den ursprünglichen Cache-Pfad
        $cachePath = \rex_path::addon('zip_install', 'cache');
        
        if (!file_exists($cachePath)) {
            \rex_dir::create($cachePath);
        }
        
        if (!is_file($tmpFile)) {
            throw new \rex_functional_exception('Die temporäre Datei existiert nicht.');
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            throw new \rex_functional_exception('Die ZIP-Datei konnte nicht geöffnet werden.');
        }

        try {
            // Prüfe erste Datei und suche package.yml
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];

                if ($i === 0 && substr($filename, -1) !== '/') {
                    throw new \rex_functional_exception('Ungültiges ZIP-Format: Erstes Element muss ein Verzeichnis sein.');
                }
                
                if ($i === 0) {
                    $foldername = $filename;
                }
                
                if (strpos($filename, 'package.yml') && !$packageFile) {
                    $packageFile = $filename;
                }
            }
            
            if (!$packageFile) {
                throw new \rex_functional_exception('Keine package.yml gefunden.');
            }

            // Extrahiere in Cache-Verzeichnis
            $zip->extractTo($cachePath);
            $zip->close();

            // Entferne unerwünschte Dateien/Ordner
            \rex_dir::delete($cachePath . '/__MACOSX');
            \rex_dir::delete($cachePath . '/.git');
            \rex_dir::delete($cachePath . '/.gitignore');
            \rex_dir::delete($cachePath . '/thumbs.db');
            
            // Lese package.yml
            $config = \rex_file::getConfig($cachePath . '/' . $foldername . 'package.yml');
            if (!isset($config['package'])) {
                throw new \rex_functional_exception('package.yml enthält keinen Package-Namen.');
            }

            // Prüfe auf Plugin
            $pluginCheck = explode('/', $config['package']);
            if (count($pluginCheck) > 1) {
                $isPlugin = true;
                // Prüfe ob Parent existiert
                if (!\rex_dir::isWritable(\rex_path::addon($pluginCheck[0]))) {
                    throw new \rex_functional_exception(\rex_i18n::msg('zip_install_plugin_parent_missing'));
                }
                
                // Kopiere Plugin
                if (!\rex_dir::copy($cachePath . '/' . $foldername, \rex_path::addon($pluginCheck[0], 'plugins/'.$pluginCheck[1]))) {
                    throw new \rex_functional_exception('Plugin konnte nicht kopiert werden.');
                }
            } else {
                // Kopiere AddOn
                if (!\rex_dir::copy($cachePath . '/' . $foldername, \rex_path::addon($config['package']))) {
                    throw new \rex_functional_exception('AddOn konnte nicht kopiert werden.');
                }
            }

            // Aufräumen
            \rex_dir::delete($cachePath . '/' . $foldername);
            
            if ($isPlugin) {
                return \rex_view::success(
                    str_replace('%%plugin%%', $config['package'], \rex_i18n::msg('zip_install_plugin_install_succeed'))
                );
            }
            
            return \rex_view::success(
                str_replace('%%addon%%', $config['package'], \rex_i18n::msg('zip_install_install_succeed'))  
            );

        } catch (\Exception $e) {
            @$zip->close();
            throw $e;
        }
    }
}
