<?php

use FriendsOfRedaxo\ZipInstall\ZipInstall;

$installer = new ZipInstall();
$message = '';

// Handle form submissions
if (rex_request_method() == 'post') {
    if ($zipFile = rex_files('zip_file')) {
        $message = $installer->handleFileUpload();
    } elseif ($url = rex_post('zip_url', 'string', '')) {
        $message = $installer->handleUrlInput($url);
    } elseif ($githubUser = rex_post('github_user', 'string', '')) {
        $repos = $installer->getGitHubRepos($githubUser);
    }
}

// Start output
if ($message) {
    echo $message;
}

// Upload form
$uploadContent = '
<form name="upload_zip_file" method="post" action="' . rex_url::currentBackendPage() . '" enctype="multipart/form-data">
    <div class="form-group">
        <label for="zip">' . rex_i18n::msg('zip_install_choose_file') . '</label>
        <input type="file" class="form-control" id="zip" name="zip_file" accept=".zip">
        <p class="help-block">' . rex_i18n::msg('zip_install_choose_info') . '</p>
    </div>
    <button type="submit" class="btn btn-primary">' . rex_i18n::msg('zip_install_upload_file') . '</button>
</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('zip_install_file_upload'));
$fragment->setVar('body', $uploadContent, false);
echo $fragment->parse('core/page/section.php');

// URL/GitHub form
$urlContent = '
<form id="zip_install_url_form" method="post" action="' . rex_url::currentBackendPage() . '">
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

// GitHub search
$githubContent = '
<form id="zip_install_github_form" method="post" action="' . rex_url::currentBackendPage() . '">
    <div class="form-group">
        <label for="github_user">' . rex_i18n::msg('zip_install_github_user') . '</label>
        <div class="input-group">
            <span class="input-group-addon">@</span>
            <input type="text" class="form-control" id="github_user" name="github_user" placeholder="FriendsOfREDAXO">
            <span class="input-group-btn">
                <button class="btn btn-primary" type="submit">' . rex_i18n::msg('zip_install_github_search') . '</button>
            </span>
        </div>
        <p class="help-block">' . rex_i18n::msg('zip_install_github_info') . '</p>
    </div>
</form>';

// Show repos if we have results
if (isset($repos) && is_array($repos)) {
    $githubContent .= '<div id="zip_install_repos">';
    foreach ($repos as $repo) {
        $githubContent .= '
        <div class="zip-panel">
            <div class="zip-panel-header">
                <h4 class="zip-panel-title"><a href="' . $repo['url'] . '" target="_blank">' . rex_escape($repo['name']) . '</a></h4>
            </div>
            <div class="zip-panel-body">
                <div class="zip-description">' . rex_escape($repo['description']) . '</div>
                <form method="post" action="' . rex_url::currentBackendPage() . '">
                    <input type="hidden" name="zip_url" value="' . $repo['url'] . '">
                    <div class="zip-button-container">
                        <button type="submit" class="btn btn-primary">Installieren</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    $githubContent .= '</div>';
} elseif (isset($repos)) {
    $githubContent .= rex_view::info(rex_i18n::msg('zip_install_invalid_github'));
}

$fragment = new rex_fragment();
$fragment->setVar('title', 'GitHub');
$fragment->setVar('body', $githubContent, false);
echo $fragment->parse('core/page/section.php');

// Add JavaScript for PJAX and dynamic form handling
?>
<script>
    $(document).on('rex:ready', function() {
        // Prevent submitting both forms when one is used
        $('#zip_url').on('input', function() {
            $('#github_user').val('');
        });
        
        $('#github_user').on('input', function() {
            $('#zip_url').val('');
        });
    });
</script>
