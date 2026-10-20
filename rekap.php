<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['owner']);
$judul = 'Rekap Transaksi';

// Rentang tanggal (default: tanggal 1 bulan ini sampai hari ini)
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$pola   = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($pola, $dari))   { $dari   = date('Y-m-01'); }
if (!preg_match($pola, $sampai)) { $sampai = date('Y-m-d'); }
if ($dari > $sampai) { $tmp = $dari; $dari = $sampai; $sampai = $tmp; }

$data = ambil("SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area
               FROM tb_transaksi t
               JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
               JOIN tb_area_parkir a ON a.id_area = t.id_area
               WHERE DATE(t.waktu_masuk) BETWEEN ? AND ?
               ORDER BY t.waktu_masuk DESC", 'ss', [$dari, $sampai]);

// Hitung ringkasan
$total_uang = 0;
$selesai    = 0;
$per_jenis  = [];
foreach ($data as $d) {
    if ($d['status'] === 'keluar') {
        $selesai++;
        $total_uang += $d['biaya_total'];
        $j = $d['jenis_kendaraan'];
        if (!isset($per_jenis[$j])) { $per_jenis[$j] = ['jumlah' => 0, 'uang' => 0]; }
        $per_jenis[$j]['jumlah']++;
        $per_jenis[$j]['uang'] += $d['biaya_total'];
    }
}

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Rekap Transaksi</h3>

<form method="get" class="card card-body mb-4 d-print-none">
  <div class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Dari Tanggal</label>
      <input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Sampai Tanggal</label>
      <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-primary">Tampilkan</button>
      <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Cetak</button>
    </div>
  </div>
</form>

<p class="text-muted">Periode: <strong><?= date('d/m/Y', strtotime($dari)) ?></strong> s.d. <strong><?= date('d/m/Y', strtotime($sampai)) ?></strong> (berdasarkan tanggal masuk)</p>

<div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
  <div class="col"><div class="card text-bg-primary"><div class="card-body"><div class="display-6 fw-bold"><?= count($data) ?></div>Total Transaksi</div></div></div>
  <div class="col"><div class="card text-bg-info"><div class="card-body"><div class="display-6 fw-bold"><?= $selesai ?></div>Transaksi Selesai</div></div></div>
  <div class="col"><div class="card text-bg-success"><div class="card-body"><div class="display-6 fw-bold"><?= rupiah($total_uang) ?></div>Total Pendapatan</div></div></div>
</div>

<?php if ($per_jenis): ?>
<div class="card mb-4">
  <div class="card-header">Pendapatan per Jenis Kendaraan</div>
  <div class="card-body table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Jenis</th><th>Jumlah Kendaraan</th><th>Pendapatan</th></tr></thead>
      <tbody>
        <?php foreach ($per_jenis as $jenis => $v): ?>
          <tr><td><?= ucfirst(e($jenis)) ?></td><td><?= $v['jumlah'] ?></td><td><?= rupiah($v['uang']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped table-sm align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Plat</th><th>Jenis</th><th>Area</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Biaya</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="fw-bold"><?= e($d['plat_nomor']) ?></td>
          <td><?= e($d['jenis_kendaraan']) ?></td>
          <td><?= e($d['nama_area']) ?></td>
          <td><?= tgl($d['waktu_masuk']) ?></td>
          <td><?= tgl($d['waktu_keluar']) ?></td>
          <td><?= $d['durasi_jam'] !== null ? $d['durasi_jam'] . ' jam' : '-' ?></td>
          <td><?= $d['status'] === 'keluar' ? rupiah($d['biaya_total']) : '-' ?></td>
          <td><?= $d['status'] === 'keluar' ? '<span class="badge text-bg-success">Selesai</span>' : '<span class="badge text-bg-danger">Parkir</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?><tr><td colspan="9" class="text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
