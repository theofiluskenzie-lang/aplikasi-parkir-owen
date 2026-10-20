<?php
require __DIR__ . '/config/koneksi.php';
wajib_login();
$role  = $_SESSION['user']['role'];
$judul = 'Dashboard';

// Container/Box [judul, nilai, warna bootstrap, halaman tujuan] //
if ($role === 'admin') {
    $kartu = [
        ['User',         skalar('SELECT COUNT(*) FROM tb_user'),       'primary',   'user.php'],
        ['Kendaraan',    skalar('SELECT COUNT(*) FROM tb_kendaraan'),  'success',   'kendaraan.php'],
        ['Jenis Tarif',  skalar('SELECT COUNT(*) FROM tb_tarif'),      'warning',   'tarif.php'],
        ['Area Parkir',  skalar('SELECT COUNT(*) FROM tb_area_parkir'), 'info',      'area.php'],
        ['Log Hari Ini', skalar('SELECT COUNT(*) FROM tb_log_aktivitas WHERE DATE(waktu_aktivitas) = CURDATE()'), 'secondary', 'log.php'],
    ];
} elseif ($role === 'petugas') {
    $kartu = [
        ['Sedang Parkir',    skalar("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'masuk'"), 'danger', 'transaksi.php'],
        ['Selesai Hari Ini', skalar("SELECT COUNT(*) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()"), 'success', 'transaksi.php'],
        ['Slot Tersedia',    skalar('SELECT COALESCE(SUM(kapasitas - terisi), 0) FROM tb_area_parkir'), 'info', 'transaksi.php'],
    ];
} else {
    $kartu = [
        ['Pendapatan Hari Ini', rupiah(skalar("SELECT COALESCE(SUM(biaya_total), 0) FROM tb_transaksi WHERE status = 'keluar' AND DATE(waktu_keluar) = CURDATE()")), 'success', 'rekap.php'],
        ['Pendapatan Bulan Ini', rupiah(skalar("SELECT COALESCE(SUM(biaya_total), 0) FROM tb_transaksi WHERE status = 'keluar' AND YEAR(waktu_keluar) = YEAR(CURDATE()) AND MONTH(waktu_keluar) = MONTH(CURDATE())")), 'primary', 'rekap.php'],
        ['Transaksi Hari Ini',  skalar('SELECT COUNT(*) FROM tb_transaksi WHERE DATE(waktu_masuk) = CURDATE()'), 'info', 'rekap.php'],
    ];
}

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-1">Selamat datang, <?= e($_SESSION['user']['nama_lengkap']) ?>!</h3>
<p class="text-muted mb-4">Anda login sebagai <strong><?= e($role) ?></strong>.</p>

<div class="row row-cols-1 row-cols-md-3 g-3">
  <?php foreach ($kartu as $k): ?>
    <div class="col">
      <div class="card text-bg-<?= $k[2] ?> h-100">
        <div class="card-body">
          <div class="display-6 fw-bold"><?= e($k[1]) ?></div>
          <div><?= e($k[0]) ?></div>
          <a href="<?= $k[3] ?>" class="stretched-link"></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
