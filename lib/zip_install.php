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
use rex_logger;
use ZipArchive;

class ZipInstall
{
    /**
     * @var rex_addon
     */
    protected rex_addon $addon;

    /**
     * @var string
     */
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
     *
     * @return string Returns a HTML string for a view message.
     */
    public function handleFileUpload(): string
    {
        if (!isset($_FILES['zip_file'])) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed'));
        }

        /** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $uploadedFile */
        $uploadedFile = $_FILES['zip_file'];

         // Validate file extension
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
         if ($fileExtension !== 'zip') {
             return rex_view::error(rex_i18n::msg('zip_install_extension_error'));
         }

         // Check mime type (as before)
        $allowedMimeTypes = ['application/zip', 'application/octet-stream'];
        if (!in_array($uploadedFile['type'], $allowedMimeTypes)) {
            
            // Check actual mime type with fileinfo extension
             if (function_exists('finfo_open')) {
                 $finfo = finfo_open(FILEINFO_MIME_TYPE);
                 $actualMimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
                 finfo_close($finfo);
                if (!in_array($actualMimeType, $allowedMimeTypes)) {
                    return rex_view::error(rex_i18n::msg('zip_install_mime_error'));
                }
             }
             else {
                 return rex_view::error(rex_i18n::msg('zip_install_mime_error'));
             }
         }

        // Check filesize
        $maxSize = $this->addon->getConfig('upload_max_size', 50) * 1024 * 1024; // Convert MB to bytes
        if ($uploadedFile['size'] > $maxSize) {
            return rex_view::error(rex_i18n::msg('zip_install_size_error', $this->addon->getConfig('upload_max_size', 20)));
        }

        $tmpFile = $this->tmpFolder . '/' . uniqid('upload_') . '.zip'; // Generate unique filename

        try {

            // Verify file content before moving
            $zip = new ZipArchive();
            if ($zip->open($uploadedFile['tmp_name']) !== true) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_zip'));
            }
            $zip->close();


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
     *
     * @param string $url The URL to process.
     * @return string Returns a HTML string for a view message.
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
        $tmpFile = $this->tmpFolder . '/' . uniqid('download_') . '.zip'; // Generate unique filename
        if (!$this->downloadFile($url, $tmpFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_url_file_not_loaded'));
        }

        return $this->installZip($tmpFile);
    }

    /**
     * Install ZIP file
     *
     * @param string $tmpFile Path to the temporary ZIP file.
     * @return string Returns a HTML string for a view message.
     */
    protected function installZip(string $tmpFile): string
    {
        $error = false;
        $isPlugin = false;
        $parentIsMissing = false;
        $folderName = '';
         /** @var string|false $packageFile */
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
                /** @var array{name: string, index: int, size: int, mtime: int, crc: int, comp_size: int, comp_method: int} $stat */
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];
                
                // Normalisiere Pfadtrenner für plattformübergreifende Kompatibilität
                $filename = str_replace('\\', '/', $filename);

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

            // Normalisiere den Pfad
            $packageFilePath = $extractPath . str_replace('\\', '/', $packageFile);

            /** @var array{package: string, version: string} $config */
            // Read package.yml
            $config = rex_file::getConfig($packageFilePath);
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
                    $sourcePath = $extractPath . rtrim($folderName, '/');
                    $destPath = rex_path::addon($pluginCheck[0], 'plugins/' . $pluginCheck[1]);
                    
                    if (!rex_dir::copy($sourcePath, $destPath)) {
                        $error = true;
                    }
                } else {
                    $parentIsMissing = true;
                    $error = true;
                }
            } else {
                // Copy addon
                $sourcePath = $extractPath . rtrim($folderName, '/');
                $destPath = rex_path::addon($config['package']);
                
                if (!rex_dir::copy($sourcePath, $destPath)) {
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
     *
     * @param string $username The GitHub username or organization name.
     * @return array Returns an array of GitHub repositories.
     */
    public function getGitHubRepos(string $username): array
    {
        $username = trim($username, '@/ ');
        $allRepos = [];
        $page = 1;
        $perPage = 100;
        
        // Get GitHub token from config if available
        $token = $this->addon->getConfig('github_token');
        
        $headers = [
            'User-Agent: REDAXOZipInstall/2.0',
            'Accept: application/vnd.github.v3+json'
        ];
        
        // Add authorization header if token is available
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers)
            ]
        ];

        $context = stream_context_create($options);
        
        while (count($allRepos) < 200) {
            $url = sprintf(
                'https://api.github.com/users/%s/repos?per_page=%d&page=%d', 
                urlencode($username), 
                $perPage, 
                $page
            );
            
            try {
                $response = @file_get_contents($url, false, $context);
                
                // Check for rate limit headers
                if (isset($http_response_header)) {
                    foreach ($http_response_header as $header) {
                        if (stripos($header, 'X-RateLimit-Remaining:') === 0) {
                            $remaining = intval(trim(substr($header, 21)));
                            if ($remaining <= 5) {
                                // Using warning level for rate limit notifications
                                \rex_logger::factory()->log(\Psr\Log\LogLevel::WARNING, 
                                    'GitHub API Rate Limit is getting low: ' . $remaining . ' requests remaining'
                                );
                            }
                        }
                    }
                }
                
                if ($response === false) {
                    // Using error level for failed requests
                    \rex_logger::factory()->log(\Psr\Log\LogLevel::ERROR, 
                        'Failed to fetch GitHub repos from: ' . $url
                    );
                    break;
                }

                $repos = json_decode($response, true);
                if (!is_array($repos) || empty($repos)) {
                    break;
                }

                foreach ($repos as $repo) {
                    if (count($allRepos) >= 200) {
                        break 2;
                    }
                    
                    if (str_starts_with($repo['name'], '.') || $repo['fork'] || $repo['archived'] || $repo['disabled']) {
                        continue;
                    }
                    
                    $downloadUrl = $repo['default_branch'] === 'main'
                        ? $repo['html_url'] . '/archive/refs/heads/main.zip'
                        : $repo['html_url'] . '/archive/refs/heads/master.zip';
                        
                    $allRepos[] = [
                        'name' => $repo['name'],
                        'description' => $repo['description'],
                        'url' => $repo['html_url'],
                        'download_url' => $downloadUrl,
                        'default_branch' => $repo['default_branch'],
                        'topics' => $repo['topics'] ?? [],
                        'homepage' => $repo['homepage'] ?? null
                    ];
                }

            } catch (Exception $e) {
                // Using logException for caught exceptions
                \rex_logger::logException($e);
                break;
            }

            $page++;
        }

        return $allRepos;
    }
    
    /**
     * Check if URL is valid and accessible
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is valid and accessible, false otherwise.
     */
    protected function isValidUrl(string $url): bool
    {
         try {
             /** @var array<int, string>|false $headers */
            $headers = @get_headers($url);
            return $headers && str_contains($headers[0], '200');
        } catch (Exception $e) {
            trigger_error('Error checking URL validity: ' . $e->getMessage(), E_USER_WARNING);
            return false; // In case of an exception consider the URL invalid
        }
    }

    /**
     * Download file from URL
     *
     * @param string $url The URL of the file to download.
     * @param string $destination The destination path to save the downloaded file.
     * @return bool True if the file was downloaded successfully, false otherwise.
     */
    protected function downloadFile(string $url, string $destination): bool
    {
        try {
             /** @var string|false $content */
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
