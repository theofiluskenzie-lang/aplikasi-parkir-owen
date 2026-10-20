<?php
require __DIR__ . '/config/koneksi.php';
catat_log('Logout');
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
