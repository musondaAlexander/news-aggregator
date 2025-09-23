<?php
require_once 'config.php';
require_once 'api/Auth.php';
Auth::logout();
header('Location: login.php');
exit;
