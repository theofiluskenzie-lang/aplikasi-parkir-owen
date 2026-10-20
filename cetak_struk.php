<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['petugas']);

$id = (int)($_GET['id'] ?? 0);
$t = ambil_satu("SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.pemilik, a.nama_area, f.tarif_per_jam, u.nama_lengkap AS petugas
                 FROM tb_transaksi t
                 JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
                 JOIN tb_area_parkir a ON a.id_area = t.id_area
                 JOIN tb_tarif f ON f.id_tarif = t.id_tarif
                 JOIN tb_user u ON u.id_user = t.id_user
                 WHERE t.id_parkir = ?", 'i', [$id]);
if (!$t) {
    die('Transaksi tidak ditemukan. <a href="transaksi.php">Kembali</a>');
}

$selesai = ($t['status'] === 'keluar');
catat_log(($selesai ? 'Cetak struk' : 'Cetak karcis') . ' transaksi #' . $id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $selesai ? 'Struk' : 'Karcis' ?> Parkir #<?= $id ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background: #e9ecef; }
    .struk { max-width: 340px; margin: 24px auto; background: #fff; padding: 20px;
             border: 1px dashed #888; font-family: 'Courier New', monospace; font-size: 14px; }
    .struk td { padding: 2px 0; vertical-align: top; }
    @media print {
      body { background: #fff; }
      .struk { border: none; margin: 0 auto; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
<div class="struk">
  <h5 class="text-center mb-0">APLIKASI PARKIR</h5>
  <div class="text-center mb-2"><?= $selesai ? 'STRUK PARKIR' : 'KARCIS MASUK' ?></div>
  <hr class="my-2">
  <table class="w-100">
    <tr><td>No. Transaksi</td><td class="text-end">#<?= $t['id_parkir'] ?></td></tr>
    <tr><td>Plat Nomor</td><td class="text-end fw-bold"><?= e($t['plat_nomor']) ?></td></tr>
    <tr><td>Jenis</td><td class="text-end"><?= e($t['jenis_kendaraan']) ?></td></tr>
    <tr><td>Area</td><td class="text-end"><?= e($t['nama_area']) ?></td></tr>
    <tr><td>Masuk</td><td class="text-end"><?= tgl($t['waktu_masuk']) ?></td></tr>
    <?php if ($selesai): ?>
      <tr><td>Keluar</td><td class="text-end"><?= tgl($t['waktu_keluar']) ?></td></tr>
      <tr><td>Durasi</td><td class="text-end"><?= $t['durasi_jam'] ?> jam</td></tr>
      <tr><td>Tarif/jam</td><td class="text-end"><?= rupiah($t['tarif_per_jam']) ?></td></tr>
    <?php endif; ?>
    <tr><td>Petugas</td><td class="text-end"><?= e($t['petugas']) ?></td></tr>
  </table>
  <hr class="my-2">
  <?php if ($selesai): ?>
    <div class="d-flex justify-content-between fs-5 fw-bold"><span>TOTAL</span><span><?= rupiah($t['biaya_total']) ?></span></div>
    <div class="text-center mt-3 small">Terima kasih. Hati-hati di jalan!</div>
  <?php else: ?>
    <div class="text-center small">Simpan karcis ini.<br>Biaya dihitung saat kendaraan keluar.</div>
  <?php endif; ?>
</div>

<div class="text-center mb-4 no-print">
  <button onclick="window.print()" class="btn btn-primary">Cetak</button>
  <a href="transaksi.php" class="btn btn-secondary">Kembali ke Transaksi</a>
</div>
</body>
</html>
