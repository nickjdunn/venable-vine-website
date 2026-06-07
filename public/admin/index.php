<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect('/admin/dashboard.php');
}
redirect('/admin/login.php');
