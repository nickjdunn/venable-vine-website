<?php require_once dirname(__DIR__) . '/includes/bootstrap.php';

$page = PageRepository::getBySlug('home');
if (!$page || !$page['is_published']) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

$pageTitle = Settings::get('site_name', 'Venable & Vine');
require ROOT . '/includes/templates/public-header.php';
render_page_layout((int) $page['id']);
require ROOT . '/includes/templates/public-footer.php';
