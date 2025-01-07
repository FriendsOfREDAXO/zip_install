<?php

use FriendsOfRedaxo\ZipInstall\ZipInstall;

$installer = new ZipInstall();

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
// GitHub search
$githubContent = '
<div class="row">
    <div class="col-sm-6">
        <form method="post">
            ' . $csrfField . '
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-github"></i></span>
                <input type="text" class="form-control" id="github_user" name="github_user" placeholder="z.B. FriendsOfREDAXO" value="">
                <span class="input-group-btn">
                    <button class="btn btn-primary" type="submit">' . rex_i18n::msg('zip_install_github_search') . '</button>
                </span>
            </div>
        </form>
    </div>
    <div class="col-sm-6">
        <form method="post">
            ' . rex_csrf_token::factory('zip_install')->getHiddenField() . '
            <input type="hidden" name="github_user" value="FriendsOfREDAXO">
            <button type="submit" class="btn btn-default"><i class="fa fa-heart"></i> FriendsOfREDAXO AddOns anzeigen</button>
        </form>
    </div>
</div>';

// Show repos if we have results
if (isset($repos) && is_array($repos)) {
    $githubContent .= '<div class="row" id="zip_install_repos">';
    $i = 0;
    foreach ($repos as $repo) {
        if ($i % 2 === 0 && $i !== 0) {
            $githubContent .= '</div><div class="row">';
        }
        $githubContent .= '
        <div class="col-sm-6">
            <div class="zip-panel">
                <div class="zip-panel-header">
                    <h4 class="zip-panel-title"><a href="' . $repo['url'] . '" target="_blank">' . rex_escape($repo['name']) . '</a></h4>
                </div>
                <div class="zip-panel-body">
                    <div class="zip-description">' . rex_escape($repo['description']) . '</div>
                    <form method="post">
                        ' . $csrfField . '
                        <input type="hidden" name="zip_url" value="' . $repo['download_url'] . '">
                        <div class="zip-button-container">
                            <button type="submit" class="btn btn-primary">Installieren</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
        $i++;
    }
    $githubContent .= '</div>';
} elseif (isset($repos)) {
    $githubContent .= rex_view::info(rex_i18n::msg('zip_install_invalid_github'));
} else {
    $githubContent .= '<p class="help-block">' . rex_i18n::msg('zip_install_github_info') . '</p>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', 'GitHub AddOns');
$fragment->setVar('body', $githubContent, false);
echo $fragment->parse('core/page/section.php');

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
echo $fragment->parse('core/page/section.php');

// URL form
$urlContent = '
<form method="post">
    ' . $csrfField . '
    <div class="form-group">
        <label for="zip_url">' . rex_i18n::msg('zip_install_url') . '</label>
        <input type="text" class="form-control" id="zip_url" name="zip_url" placeholder="https://github.com/FriendsOfREDAXO/demo_base/archive/refs/heads/main.zip">
    </div>
    <button type="submit" class="btn btn-primary">ZIP herunterladen</button>
</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'ZIP URL');
$fragment->setVar('body', $urlContent, false);
echo $fragment->parse('core/page/section.php');
