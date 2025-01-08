<?php

use FriendsOfRedaxo\ZipInstall\ZipInstall;

$installer = new ZipInstall();

// Define common GitHub users/organizations
$commonAuthors = [
    'FriendsOfREDAXO',
    'yakamara',
    'tbaddade',
];

// Handle form submissions
if (rex_request_method() == 'post') {
    // CSRF protection check
    if (rex_csrf_token::factory('zip_install')->isValid()) {
        if ($zipFile = rex_files('zip_file')) {
            echo $installer->handleFileUpload();
        } elseif ($url = rex_post('zip_url', 'string', '')) {
            echo $installer->handleUrlInput($url);
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
        <div class="col-sm-6">
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
