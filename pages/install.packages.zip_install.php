<?php

use FriendsOfRedaxo\ZipInstall\ZipInstall;

$installer = new ZipInstall();

// Define common GitHub users/organizations
$commonAuthors = [
    'FriendsOfREDAXO',
    'yakamara',
    'alexplusde',
    'AndiLeni',
    'danspringer',
    'dtpop',
    'eaCe',
    'iceman-fx',
    'KLXM',
    'tbaddade',
    'TobiasKrais'
];

// Handle form submissions
if (rex_request_method() == 'post') {
    // CSRF protection check
    if (rex_csrf_token::factory('zip_install')->isValid()) {
        if ($zipFile = rex_files('zip_file')) {
            $result = $installer->handleFileUploadWithResult();
            echo $result['message'];
            
            // Add direct installation link if addon was successfully installed
            if (isset($result['success']) && $result['success'] && $result['addon_key']) {
                // Always show installation link if addon was successfully extracted
                if (rex_addon::exists($result['addon_key'])) {
                    $addon = rex_addon::get($result['addon_key']);
                    
                    if (!$addon->isInstalled()) {
                        // Generate the correct installation link like the install addon
                        $installUrl = rex_url::currentBackendPage([
                            'page' => 'packages',
                            'package' => $result['addon_key'],
                            'function' => 'install',
                            'rex-api-call' => 'package',
                            '_csrf_token' => rex_csrf_token::factory('rex_api_package')->getValue()
                        ]);
                        
                        echo '<div class="alert alert-success">';
                        echo '<h4><i class="fa fa-check-circle"></i> AddOn erfolgreich heruntergeladen</h4>';
                        echo '<p>Das AddOn <strong>' . rex_escape($result['addon_key']) . '</strong> wurde erfolgreich entpackt und ist bereit zur Installation.</p>';
                        echo '<p><a href="' . $installUrl . '" class="btn btn-primary btn-lg"><i class="fa fa-download"></i> AddOn jetzt installieren</a></p>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> AddOn ist bereits installiert.</div>';
                    }
                } else {
                    // Even if addon doesn't exist in the system check, still try to provide a link
                    // This can happen with GitHub repos that have different folder structures
                    $installUrl = rex_url::currentBackendPage([
                        'page' => 'packages',
                        'package' => $result['addon_key'],
                        'function' => 'install',
                        'rex-api-call' => 'package',
                        '_csrf_token' => rex_csrf_token::factory('rex_api_package')->getValue()
                    ]);
                    
                    echo '<div class="alert alert-warning">';
                    echo '<h4><i class="fa fa-exclamation-triangle"></i> AddOn heruntergeladen</h4>';
                    echo '<p>Das AddOn wurde entpackt, konnte aber nicht automatisch erkannt werden.</p>';
                    echo '<p><a href="' . $installUrl . '" class="btn btn-primary btn-lg"><i class="fa fa-download"></i> AddOn installieren (falls verfügbar)</a></p>';
                    echo '</div>';
                }
            }
        } elseif ($url = rex_post('zip_url', 'string', '')) {
            $result = $installer->handleUrlInputWithResult($url);
            echo $result['message'];
            
            // Add direct installation link if addon was successfully installed
            if (isset($result['success']) && $result['success'] && $result['addon_key']) {
                // Force addon detection refresh to ensure newly extracted addons are recognized
                rex_package_manager::synchronizeWithFileSystem();
                
                // Always show installation link if addon was successfully extracted
                if (rex_addon::exists($result['addon_key'])) {
                    $addon = rex_addon::get($result['addon_key']);
                    
                    if (!$addon->isInstalled()) {
                        // Generate the correct installation link like the install addon
                        $installUrl = rex_url::currentBackendPage([
                            'page' => 'packages',
                            'package' => $result['addon_key'],
                            'function' => 'install',
                            'rex-api-call' => 'package',
                            '_csrf_token' => rex_csrf_token::factory('rex_api_package')->getValue()
                        ]);
                        
                        echo '<div class="alert alert-success">';
                        echo '<h4><i class="fa fa-check-circle"></i> AddOn erfolgreich heruntergeladen</h4>';
                        echo '<p>Das AddOn <strong>' . rex_escape($result['addon_key']) . '</strong> wurde erfolgreich entpackt und ist bereit zur Installation.</p>';
                        echo '<p><a href="' . $installUrl . '" class="btn btn-primary btn-lg"><i class="fa fa-download"></i> AddOn jetzt installieren</a></p>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> AddOn ist bereits installiert.</div>';
                    }
                } else {
                    // Even if addon doesn't exist in the system check, still try to provide a link
                    // This can happen with GitHub repos that have different folder structures
                    $installUrl = rex_url::currentBackendPage([
                        'page' => 'packages',
                        'package' => $result['addon_key'],
                        'function' => 'install',
                        'rex-api-call' => 'package',
                        '_csrf_token' => rex_csrf_token::factory('rex_api_package')->getValue()
                    ]);
                    
                    echo '<div class="alert alert-warning">';
                    echo '<h4><i class="fa fa-exclamation-triangle"></i> AddOn heruntergeladen</h4>';
                    echo '<p>Das AddOn wurde entpackt, konnte aber nicht automatisch erkannt werden.</p>';
                    echo '<p><a href="' . $installUrl . '" class="btn btn-primary btn-lg"><i class="fa fa-download"></i> AddOn installieren (falls verfügbar)</a></p>';
                    echo '</div>';
                }
            }
        } elseif ($githubUser = rex_post('github_user', 'string', '')) {
            $repos = $installer->getGitHubRepos($githubUser);
        }
    } else {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    }
}

$csrfField = rex_csrf_token::factory('zip_install')->getHiddenField();

// Create datalist HTML
$datalistHtml = '<datalist id="authors">';
foreach ($commonAuthors as $author) {
    $datalistHtml .= '<option value="' . rex_escape($author) . '"></option>';
}
$datalistHtml .= '</datalist>';

// Main content with new layout
$content = '<div class="row">';

// Left column (8 columns) - GitHub search
$content .= '<div class="col-sm-8">';

// Get the current search term
$currentSearch = rex_post('github_user', 'string', '');

// GitHub section
$githubContent = '
<form method="post" class="mb-4">
    ' . $csrfField . '
    <div class="input-group input-group-lg">
        <span class="input-group-addon"><i class="fa fa-github"></i></span>
        <input type="text" class="form-control" id="github_user" name="github_user" 
               placeholder="' . rex_i18n::msg('zip_install_github_search_placeholder') . '" list="authors" 
               value="' . rex_escape($currentSearch) . '">
        <span class="input-group-btn">
            <button class="btn btn-primary" type="submit">' . rex_i18n::msg('zip_install_github_search') . '</button>
        </span>
    </div>
    ' . $datalistHtml . '
</form>';

// Show repos if we have results
if (isset($repos) && is_array($repos)) {
    $githubContent .= '<div class="row" id="zip_install_repos">';
    foreach ($repos as $repo) {
        $githubContent .= '
        <div class="col-sm-12">
            <div class="zip-panel">
                <div class="zip-panel-header">
                    <div class="zip-panel-header-content">
                        <h4 class="zip-panel-title">
                            <a href="' . $repo['url'] . '" target="_blank">' . rex_escape($repo['name']) . '</a>
                        </h4>
                        <form method="post" class="zip-download-form">
                            ' . $csrfField . '
                            <input type="hidden" name="zip_url" value="' . $repo['download_url'] . '">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa fa-download"></i> ' . rex_i18n::msg('zip_install_install') . '
                            </button>
                        </form>
                    </div>
                </div>
                <div class="zip-panel-body">
                    <div class="zip-description">' . rex_escape($repo['description']) . '</div>
                </div>
            </div>
        </div>';
    }
    $githubContent .= '</div>';
} elseif (isset($repos)) {
    $githubContent .= rex_view::info(rex_i18n::msg('zip_install_invalid_github'));
} else {
    $githubContent .= '<p class="help-block">' . rex_i18n::msg('zip_install_github_info') . '</p>';
}

$content .= $githubContent . '</div>';

// Right column (4 columns) - Upload and URL forms
$content .= '<div class="col-sm-4">';

// Upload form
$uploadContent = '
<form method="post" enctype="multipart/form-data">
    ' . $csrfField . '
    <div class="form-group">
        <label for="zip">' . rex_i18n::msg('zip_install_choose_file') . '</label>
        <input type="file" class="form-control" id="zip" name="zip_file" accept=".zip">
        <p class="help-block">' . rex_i18n::rawMsg('zip_install_choose_info') . '</p>
    </div>
    <button type="submit" class="btn btn-primary">' . rex_i18n::msg('zip_install_upload_file') . '</button>
</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('zip_install_file_upload'));
$fragment->setVar('body', $uploadContent, false);
$content .= $fragment->parse('core/page/section.php');

// URL form
$urlContent = '
<form method="post">
    ' . $csrfField . '
    <div class="form-group">
        <label for="zip_url">' . rex_i18n::msg('zip_install_url') . '</label>
        <input type="text" class="form-control" id="zip_url" name="zip_url" 
               placeholder="https://github.com/FriendsOfREDAXO/demo_base/archive/refs/heads/main.zip">
    </div>
    <button type="submit" class="btn btn-primary">' . rex_i18n::msg('zip_install_download') . '</button>
</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'ZIP URL');
$fragment->setVar('body', $urlContent, false);
$content .= $fragment->parse('core/page/section.php');

$content .= '</div>'; // End right column
$content .= '</div>'; // End row

// Output the full content
$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('zip_install_title'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
