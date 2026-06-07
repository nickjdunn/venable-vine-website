<?php

define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/helpers.php';
define('PUBLIC_ROOT', resolve_public_root(ROOT));

// #region agent log
agent_debug_log('A', 'bootstrap.php', 'PUBLIC_ROOT resolved', [
    'PUBLIC_ROOT' => PUBLIC_ROOT,
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'roots_match' => paths_match(PUBLIC_ROOT, $_SERVER['DOCUMENT_ROOT'] ?? ''),
    'public_dir_exists' => is_dir(ROOT . '/public'),
    'public_html_exists' => is_dir(ROOT . '/public_html'),
]);
// #endregion

session_start();
require_once ROOT . '/includes/Database.php';
require_once ROOT . '/includes/Auth.php';
require_once ROOT . '/includes/Csrf.php';
require_once ROOT . '/includes/Settings.php';
require_once ROOT . '/includes/Upload.php';
require_once ROOT . '/includes/Mailer.php';
require_once ROOT . '/includes/NewsletterService.php';

$repos = [
    'PageRepository',
    'MenuRepository',
    'EventRepository',
    'MediaRepository',
    'ReviewRepository',
    'ContactRepository',
    'NewsletterRepository',
    'UserRepository',
];
foreach ($repos as $repo) {
    require_once ROOT . '/includes/repositories/' . $repo . '.php';
}

require_once ROOT . '/includes/sections/render.php';
require_once ROOT . '/includes/layout/render.php';
require_once ROOT . '/includes/Logger.php';

if (Logger::isAdminRequest() || Logger::isApiRequest()) {
    Logger::registerHandlers();
}
