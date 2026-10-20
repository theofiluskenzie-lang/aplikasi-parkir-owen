<?php
// Pintu masuk: arahkan ke dashboard kalau sudah login //
require __DIR__ . '/config/koneksi.php';
header('Location: ' . BASE_URL . (isset($_SESSION['user']) ? 'dashboard.php' : 'login.php'));
exit;
