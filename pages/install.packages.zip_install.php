<?php

namespace FriendsOfRedaxo\ZipInstall;

use Exception;
use rex;
use rex_addon;
use rex_dir;
use rex_file;
use rex_i18n;
use rex_path;
use rex_request;
use rex_response;
use rex_url;
use rex_view;
use ZipArchive;

class ZipInstall
{
    protected rex_addon $addon;
    protected string $tmpFolder;

    public function __construct()
    {
        $this->addon = rex_addon::get('zip_install');
        $this->tmpFolder = $this->addon->getCachePath('tmp_uploads');

        // Ensure temp folder exists
        if (!is_dir($this->tmpFolder)) {
            try {
                rex_dir::create($this->tmpFolder);
            } catch (Exception $e) {
                // Log the exception or handle it as needed
                trigger_error('Error creating temp directory: ' . $e->getMessage(), E_USER_WARNING);
                // Possibly throw another exception or return an error message
                return;
            }
        }
    }

    /**
     * Handle file upload from form
     */
    public function handleFileUpload(): string
    {
        if (!isset($_FILES['zip_file'])) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed'));
        }

        $uploadedFile = $_FILES['zip_file'];

        // Check filesize
        $maxSize = $this->addon->getConfig('upload_max_size', 20) * 1024 * 1024; // Convert MB to bytes
        if ($uploadedFile['size'] > $maxSize) {
            return rex_view::error(rex_i18n::msg('zip_install_size_error', $this->addon->getConfig('upload_max_size', 20)));
        }

        $tmpFile = $this->tmpFolder . '/temp.zip';
        try {
            if (!move_uploaded_file($uploadedFile['tmp_name'], $tmpFile)) {
                 throw new Exception(rex_i18n::msg('zip_install_upload_failed'));
            }
        } catch (Exception $e) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed') . ' ' . $e->getMessage());
        }

        return $this->installZip($tmpFile);
    }

    /**
     * Handle URL input (direct ZIP or GitHub URL)
     */
    public function handleUrlInput(string $url): string
    {
        if (empty($url)) {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        // Remove trailing slash if exists
        $url = rtrim($url, '/');

        // Check if it's a GitHub repository URL
        if (preg_match('/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)(\/tree\/([^\/]+))?$/i', $url, $matches)) {
            $owner = $matches[1];
            $repo = $matches[2];
            $branch = $matches[4] ?? null;

            if ($branch) {
                $downloadUrl = "https://github.com/$owner/$repo/archive/refs/heads/$branch.zip";
            } else {
                // Try main/master branch
                $downloadUrl = "https://github.com/$owner/$repo/archive/refs/heads/main.zip";

                // If main doesn't exist, try master
                if (!$this->isValidUrl($downloadUrl)) {
                    $downloadUrl = "https://github.com/$owner/$repo/archive/refs/heads/master.zip";
                }
            }
            $url = $downloadUrl;
        }

        // Download file
        $tmpFile = $this->tmpFolder . '/download.zip';
        if (!$this->downloadFile($url, $tmpFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_url_file_not_loaded'));
        }

        return $this->installZip($tmpFile);
    }

    /**
     * Install ZIP file
     */
    protected function installZip(string $tmpFile): string
    {
        $error = false;
        $isPlugin = false;
        $parentIsMissing = false;
        $folderName = '';
        $packageFile = false;
        $extractPath = $this->tmpFolder . '/extract/'; // Define here to ensure its existence for the finally block


        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            // Check first entry and look for package.yml
            $i = 1;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];

                if ($i == 0) {
                    // First entry must be a directory
                    if (!str_ends_with($filename, '/')) {
                        $error = true;
                        break;
                    }
                    $folderName = $filename;
                }

                // Find first package.yml
                if (!$packageFile && str_contains($filename, 'package.yml')) {
                    $packageFile = $filename;
                }
            }

            if ($error || !$packageFile) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            // Extract to temp folder
             if (!is_dir($extractPath)) {
                rex_dir::create($extractPath);
            }
            
            if (!$zip->extractTo($extractPath)) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }
            $zip->close();


            // Read package.yml
            $config = rex_file::getConfig($extractPath . $packageFile);
            if (empty($config['package'])) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            // Handle plugins
            $pluginCheck = explode('/', $config['package']);
            if (count($pluginCheck) > 1) {
                $isPlugin = true;
                // Check if parent exists
                if (rex_dir::isWritable(rex_path::addon($pluginCheck[0]))) {
                    // Copy plugin to correct location
                    if (!rex_dir::copy(
                        $extractPath . $folderName,
                        rex_path::addon($pluginCheck[0], 'plugins/' . $pluginCheck[1])
                    )) {
                        $error = true;
                    }
                } else {
                    $parentIsMissing = true;
                    $error = true;
                }
            } else {
                // Copy addon
                if (!rex_dir::copy($extractPath . $folderName, rex_path::addon($config['package']))) {
                    $error = true;
                }
            }

        } catch (Exception $e) {
            $error = true;
             trigger_error('Error during installation: ' . $e->getMessage(), E_USER_WARNING);
        } finally {
            // Cleanup
            rex_dir::delete($extractPath);
            @unlink($tmpFile); // Use @ to suppress warnings if unlink fails
        }

        if (!$error) {
            if ($isPlugin) {
                return rex_view::success(str_replace(
                    '%%plugin%%',
                    $config['package'],
                    rex_i18n::rawMsg('zip_install_plugin_install_succeed')
                ));
            }
            return rex_view::success(str_replace(
                '%%addon%%',
                $config['package'],
                rex_i18n::rawMsg('zip_install_install_succeed')
            ));
        }

        if ($parentIsMissing) {
            return rex_view::error(rex_i18n::msg('zip_install_plugin_parent_missing'));
        }
        return rex_view::error(rex_i18n::msg('zip_install_invalid_addon'));
    }

    /**
     * Get GitHub repositories for user/organization
     */
    public function getGitHubRepos(string $username): array
    {
        $username = trim($username, '@/ '); // Remove @ and slashes if present
        $url = 'https://api.github.com/users/' . urlencode($username) . '/repos?per_page=100'; // Increased per_page and added for limit
        $allRepos = [];
        $page = 1;
        $perPage = 100; // You can fetch max 100 per page
        
        while (count($allRepos) < 200) { // Limit total repos to 200
        
            $url = 'https://api.github.com/users/' . urlencode($username) . '/repos?per_page=' . $perPage . '&page=' . $page;
           
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: REDAXOZipInstall',
                        'Accept: application/vnd.github.v3+json'
                    ]
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);


             if ($response === false) {
                break; // if API call fails, stop pagination
                
            }

             $repos = json_decode($response, true);
             
            if (!is_array($repos)) {
                break; // if response is not array, stop pagination
            }

            if (empty($repos)) {
                break; // No more repos, stop pagination
            }
            
             
           // Filter and format repos
            foreach ($repos as $repo) {
                if (count($allRepos) >= 200){
                    break 2; // Exit both foreach and while loop
                }
                
                // Check if the repo name starts with a dot
                 if (str_starts_with($repo['name'], '.')) {
                    continue; // Skip this repository
                }
                
                 if (!$repo['fork'] && !$repo['archived'] && !$repo['disabled']) {
                     $downloadUrl = $repo['default_branch'] === 'main'
                        ? $repo['html_url'] . '/archive/refs/heads/main.zip'
                        : $repo['html_url'] . '/archive/refs/heads/master.zip';
                    
                    $allRepos[] = [
                        'name' => $repo['name'],
                        'description' => $repo['description'],
                        'url' => $repo['html_url'],
                        'download_url' => $downloadUrl,
                        'default_branch' => $repo['default_branch']
                    ];
                }
            }

            $page++; // Increment page for next call
        }

        return $allRepos;
    }

    /**
     * Check if URL is valid and accessible
     */
    protected function isValidUrl(string $url): bool
    {
         try {
            $headers = @get_headers($url);
            return $headers && str_contains($headers[0], '200');
        } catch (Exception $e) {
             trigger_error('Error checking URL validity: ' . $e->getMessage(), E_USER_WARNING);
            return false; // In case of an exception consider the URL invalid
        }
    }

    /**
     * Download file from URL
     */
    protected function downloadFile(string $url, string $destination): bool
    {
        try {
            $content = @file_get_contents($url);
            if ($content === false) {
                return false;
            }
            return rex_file::put($destination, $content);
        } catch (Exception $e) {
            trigger_error('Error downloading file: ' . $e->getMessage(), E_USER_WARNING);
            return false;
        }
    }
}
