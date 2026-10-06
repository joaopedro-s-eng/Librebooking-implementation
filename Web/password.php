<?php

define('ROOT_DIR', '../');

require_once(ROOT_DIR . 'Web/dashboard.php');
exit;
$page = new PasswordPage();
$page->PageLoad();
