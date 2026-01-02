<?php

use FriendsOfRedaxo\ZipInstall\ZipInstall;

$addon = rex_addon::get('zip_install');
$csrf = rex_csrf_token::factory('zip_install_settings');

// Handle form submission
if (rex_post('save', 'boolean')) {
    if ($csrf->isValid()) {
        $githubToken = rex_post('github_token', 'string', '');
        $uploadMaxSize = rex_post('upload_max_size', 'int', 50);
        
        // Validate upload size
        if ($uploadMaxSize < 1 || $uploadMaxSize > 500) {
            echo rex_view::error(rex_i18n::msg('zip_install_settings_upload_max_size_invalid'));
        } else {
            // Save settings
            rex_config::set('zip_install', 'github_token', $githubToken);
            rex_config::set('zip_install', 'upload_max_size', $uploadMaxSize);
            
            echo rex_view::success(rex_i18n::msg('zip_install_settings_saved'));
        }
    } else {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    }
}

// Get current settings
$githubToken = rex_config::get('zip_install', 'github_token', '');
$uploadMaxSize = rex_config::get('zip_install', 'upload_max_size', 50);

// Get Rate Limit Info
$rateLimitInfo = '';
try {
    $installer = new ZipInstall();
    $rateLimit = $installer->getRateLimit();
    
    if ($rateLimit) {
        $resetTime = date('H:i:s', $rateLimit['reset']);
        
        $rateLimitInfo = '<div class="alert alert-info" style="margin-top: 10px;">';
        $rateLimitInfo .= '<strong>GitHub API Status:</strong><br>';
        $rateLimitInfo .= 'Limit: ' . $rateLimit['limit'] . ' Requests/h<br>';
        $rateLimitInfo .= 'Remaining: <strong>' . $rateLimit['remaining'] . '</strong><br>';
        $rateLimitInfo .= 'Reset: ' . $resetTime;
        $rateLimitInfo .= '</div>';
    }
} catch (Exception $e) {
    // Ignore
}

// Build form
$content = '';
$content .= '<fieldset>';

// GitHub Token field
$formElements = [];

$n = [];
$n['label'] = '<label for="github_token">' . rex_i18n::msg('zip_install_settings_github_token') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="github_token" name="github_token" value="' . rex_escape($githubToken) . '" placeholder="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />';
$n['note'] = rex_i18n::msg('zip_install_settings_github_token_info') . '<br><a href="https://github.com/settings/tokens/new" target="_blank" rel="noopener noreferrer"><i class="rex-icon fa-external-link"></i> ' . rex_i18n::msg('zip_install_settings_github_token_create') . '</a>';
if ($rateLimitInfo) {
    $n['note'] .= $rateLimitInfo;
}
$formElements[] = $n;

// Upload Max Size field
$n = [];
$n['label'] = '<label for="upload_max_size">' . rex_i18n::msg('zip_install_settings_upload_max_size') . '</label>';
$n['field'] = '<input class="form-control" type="number" id="upload_max_size" name="upload_max_size" value="' . $uploadMaxSize . '" min="1" max="500" />';
$n['note'] = rex_i18n::msg('zip_install_settings_upload_max_size_info');
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$content .= '</fieldset>';

// Form buttons
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="1">' . rex_i18n::msg('zip_install_settings_save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');
$content .= $buttons;

// Wrap in form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('zip_install_settings'), false);
$fragment->setVar('body', $content, false);
$content = $fragment->parse('core/page/section.php');

$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
    ' . $csrf->getHiddenField() . '
    ' . $content . '
</form>';

echo $content;
