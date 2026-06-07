<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::logout();
redirect('/admin/login.php');
