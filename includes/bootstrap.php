<?php

define('ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', is_dir(ROOT . '/public') ? ROOT . '/public' : ROOT . '/public_html');

session_start();

require_once ROOT . '/includes/helpers.php';
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
    'GalleryRepository',
    'ReviewRepository',
    'ContactRepository',
    'NewsletterRepository',
    'UserRepository',
];
foreach ($repos as $repo) {
    require_once ROOT . '/includes/repositories/' . $repo . '.php';
}

require_once ROOT . '/includes/sections/render.php';
