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
    private const GITHUB_API_URL = 'https://api.github.com';
    private const GITHUB_MAIN_BRANCHES = ['main', 'master'];

    private rex_addon $addon;
    private string $tmpFolder;

    public function __construct()
    {
        $this->addon = rex_addon::get('zip_install');
        $this->tmpFolder = $this->addon->getCachePath('tmp_uploads');
        
        // Ensure temp folder exists
        if (!is_dir($this->tmpFolder)) {
            rex_dir::create($this->tmpFolder);
        }
    }

    /**
     * Handle file upload from form
     */
    public function handleFileUpload(): string
    {
        $uploadedFile = $_FILES['zip_file'] ?? null;
        if (!$uploadedFile) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed'));
        }

        // Check filesize
        $maxSize = $this->addon->getConfig('upload_max_size') * 1024 * 1024; // Convert MB to bytes
        if ($uploadedFile['size'] > $maxSize) {
            return rex_view::error(rex_i18n::msg('zip_install_size_error', $this->addon->getConfig('upload_max_size')));
        }

        // Check if it's a ZIP
        if ($uploadedFile['type'] !== 'application/zip' && pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'zip') {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_file'));
        }

        $tmpFile = $this->tmpFolder . '/'. basename($uploadedFile['name']);
        
        if (!move_uploaded_file($uploadedFile['tmp_name'], $tmpFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed'));
        }

        return $this->handleZipFile($tmpFile);
    }

    /**
     * Handle URL input (direct ZIP or GitHub URL)
     */
    public function handleUrlInput(string $url): string 
    {
        if (empty($url)) {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        // Check if it's a GitHub repository URL
        if (preg_match('/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)(\/tree\/([^\/]+))?$/i', $url, $matches)) {
            $owner = $matches[1];
            $repo = $matches[2];
            $branch = $matches[4] ?? null;

            return $this->handleGitHubRepo($owner, $repo, $branch);
        }

        // Direct ZIP URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        $tmpFile = $this->tmpFolder . '/download.zip';
        if (!$this->downloadFile($url, $tmpFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        return $this->handleZipFile($tmpFile);
    }

    /**
     * Handle GitHub repository
     */
    private function handleGitHubRepo(string $owner, string $repo, ?string $branch = null): string
    {
        if ($branch) {
            $downloadUrl = "https://github.com/$owner/$repo/archive/refs/heads/$branch.zip";
        } else {
            // Try main branches
            foreach (self::GITHUB_MAIN_BRANCHES as $mainBranch) {
                $downloadUrl = "https://github.com/$owner/$repo/archive/refs/heads/$mainBranch.zip";
                $tmpFile = $this->tmpFolder . '/github.zip';
                
                if ($this->downloadFile($downloadUrl, $tmpFile)) {
                    return $this->handleZipFile($tmpFile);
                }
            }
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        $tmpFile = $this->tmpFolder . '/github.zip';
        if (!$this->downloadFile($downloadUrl, $tmpFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_invalid_url'));
        }

        return $this->handleZipFile($tmpFile);
    }

    /**
     * Get GitHub repositories for user/organization
     */
    public function getGitHubRepos(string $username): array
    {
        $url = self::GITHUB_API_URL . '/users/' . urlencode($username) . '/repos';
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: REDAXOZipInstall',
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ];

        // Add token if configured
        $token = $this->addon->getConfig('github_token');
        if ($token) {
            $options['http']['header'][] = 'Authorization: token ' . $token;
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return [];
        }

        $repos = json_decode($response, true);
        if (!is_array($repos)) {
            return [];
        }

        // Filter and format repos
        $filtered = [];
        foreach ($repos as $repo) {
            if (!$repo['fork'] && !$repo['archived'] && !$repo['disabled']) {
                $filtered[] = [
                    'name' => $repo['name'],
                    'description' => $repo['description'],
                    'url' => $repo['html_url'],
                    'default_branch' => $repo['default_branch']
                ];
            }
        }

        return $filtered;
    }

    /**
     * Process uploaded/downloaded ZIP file
     */
    private function handleZipFile(string $zipFile): string
    {
        if (!file_exists($zipFile)) {
            return rex_view::error(rex_i18n::msg('zip_install_upload_failed'));
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_file'));
            }

            // Get the first directory in the ZIP
            $firstDir = $this->getFirstDirectory($zip);
            if (!$firstDir) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            // Extract to temp directory
            $extractPath = $this->tmpFolder . '/extract/';
            if (!$zip->extractTo($extractPath)) {
                throw new Exception(rex_i18n::msg('zip_install_upload_failed'));
            }
            $zip->close();

            // Check if it's a valid addon
            $packageFile = $extractPath . $firstDir . 'package.yml';
            if (!file_exists($packageFile)) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            $config = rex_file::getConfig($packageFile);
            if (empty($config['package'])) {
                throw new Exception(rex_i18n::msg('zip_install_invalid_addon'));
            }

            // Handle plugin
            $isPlugin = str_contains($config['package'], '/');
            if ($isPlugin) {
                [$addon, $plugin] = explode('/', $config['package']);
                if (!rex_addon::exists($addon)) {
                    throw new Exception(rex_i18n::msg('zip_install_missing_addon'));
                }
                $targetDir = rex_path::plugin($addon, $plugin);
            } else {
                $targetDir = rex_path::addon($config['package']);
            }

            // Move to final destination
            if (!rex_dir::copy($extractPath . $firstDir, $targetDir)) {
                throw new Exception(rex_i18n::msg('zip_install_install_failed'));
            }

            // Cleanup
            rex_dir::delete($extractPath);
            unlink($zipFile);

            return rex_view::success(rex_i18n::msg('zip_install_install_succeed'));

        } catch (Exception $e) {
            // Cleanup on error
            if (isset($extractPath) && is_dir($extractPath)) {
                rex_dir::delete($extractPath);
            }
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            return rex_view::error($e->getMessage());
        }
    }

    /**
     * Get first directory in ZIP file
     */
    private function getFirstDirectory(ZipArchive $zip): ?string
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/' && strpos($name, '/', 1) === false) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Download file from URL
     */
    private function downloadFile(string $url, string $destination): bool
    {
        $content = @file_get_contents($url);
        if ($content === false) {
            return false;
        }
        return rex_file::put($destination, $content);
    }
}
