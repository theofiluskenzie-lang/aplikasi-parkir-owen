<?php
// Variabel yang dibutuhkan: $judul. Panggil wajib_login() sebelum include file ini.
$u = $_SESSION['user'];
$menu = [
    'admin'   => [['dashboard.php', 'Dashboard'], ['user.php', 'User'], ['tarif.php', 'Tarif'],
                  ['area.php', 'Area Parkir'], ['kendaraan.php', 'Kendaraan'], ['log.php', 'Log Aktivitas']],
    'petugas' => [['dashboard.php', 'Dashboard'], ['transaksi.php', 'Transaksi']],
    'owner'   => [['dashboard.php', 'Dashboard'], ['rekap.php', 'Rekap Transaksi']],
];
$warna_role = ['admin' => 'danger', 'petugas' => 'success', 'owner' => 'warning'];
$halaman_ini = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> Aplikasi Parkir</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 d-print-none">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php">Aplikasi Parkir</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuUtama">
      <ul class="navbar-nav me-auto">
        <?php foreach ($menu[$u['role']] as $m): ?>
          <li class="nav-item">
            <a class="nav-link <?= $halaman_ini === $m[0] ? 'active fw-bold' : '' ?>"
               href="<?= BASE_URL . $m[0] ?>"><?= $m[1] ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <span class="navbar-text me-3">
        <?= e($u['nama_lengkap']) ?>
        <span class="badge text-bg-<?= $warna_role[$u['role']] ?>"><?= e($u['role']) ?></span>
      </span>
      <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container pb-5">
<?php if (!empty($_SESSION['flash'])): list($tipe_flash, $pesan_flash) = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= $tipe_flash ?> alert-dismissible fade show d-print-none">
    <?= e($pesan_flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
