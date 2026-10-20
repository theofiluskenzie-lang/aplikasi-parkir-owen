<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['admin']);
$judul = 'Kelola Kendaraan';
$daftar_jenis = ['motor', 'mobil', 'lainnya'];   // sama dengan jenis pada tabel tarif //

// Hapus //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    try {
        eksekusi('DELETE FROM tb_kendaraan WHERE id_kendaraan = ?', 'i', [$id]);
        catat_log('Hapus kendaraan ID ' . $id);
        flash('Kendaraan berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        flash('Kendaraan tidak bisa dihapus karena sudah punya riwayat transaksi.', 'danger');
    }
    header('Location: kendaraan.php');
    exit;
}

// Simpan //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id      = (int)$_POST['id_kendaraan'];
    $plat    = strtoupper(preg_replace('/\s+/', ' ', trim($_POST['plat_nomor'])));
    $jenis   = $_POST['jenis_kendaraan'];
    $warna   = trim($_POST['warna']);
    $pemilik = trim($_POST['pemilik']);

    $dobel = ambil_satu('SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ? AND id_kendaraan <> ?', 'si', [$plat, $id]);

    if ($plat === '' || !in_array($jenis, $daftar_jenis)) {
        flash('Plat nomor dan jenis kendaraan wajib diisi.', 'danger');
    } elseif ($dobel) {
        flash('Plat nomor ' . $plat . ' sudah terdaftar.', 'danger');
    } elseif ($id > 0) {
        eksekusi('UPDATE tb_kendaraan SET plat_nomor = ?, jenis_kendaraan = ?, warna = ?, pemilik = ? WHERE id_kendaraan = ?',
                 'ssssi', [$plat, $jenis, $warna, $pemilik, $id]);
        catat_log('Ubah kendaraan ' . $plat);
        flash('Data kendaraan berhasil diubah.');
    } else {
        eksekusi('INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES (?, ?, ?, ?, ?)',
                 'ssssi', [$plat, $jenis, $warna, $pemilik, $_SESSION['user']['id_user']]);
        catat_log('Tambah kendaraan ' . $plat);
        flash('Kendaraan baru berhasil ditambahkan.');
    }
    header('Location: kendaraan.php');
    exit;
}

$cari = trim($_GET['cari'] ?? '');
$like = '%' . $cari . '%';
$edit = isset($_GET['edit']) ? ambil_satu('SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?', 'i', [(int)$_GET['edit']]) : null;
$data = ambil("SELECT k.*, (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_kendaraan = k.id_kendaraan AND t.status = 'masuk') AS sedang_parkir
               FROM tb_kendaraan k
               WHERE k.plat_nomor LIKE ? OR k.pemilik LIKE ?
               ORDER BY k.id_kendaraan DESC", 'ss', [$like, $like]);

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Kelola Kendaraan</h3>

<div class="card mb-4">
  <div class="card-header"><?= $edit ? 'Edit Kendaraan' : 'Tambah Kendaraan' ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id_kendaraan" value="<?= $edit['id_kendaraan'] ?? 0 ?>">
      <div class="col-md-3">
        <label class="form-label">Plat Nomor</label>
        <input type="text" name="plat_nomor" class="form-control" maxlength="15" placeholder="BP 1234 AB" value="<?= e($edit['plat_nomor'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Jenis Kendaraan</label>
        <select name="jenis_kendaraan" class="form-select">
          <?php foreach ($daftar_jenis as $j): ?>
            <option value="<?= $j ?>" <?= ($edit['jenis_kendaraan'] ?? '') === $j ? 'selected' : '' ?>><?= ucfirst($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Warna</label>
        <input type="text" name="warna" class="form-control" maxlength="20" value="<?= e($edit['warna'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Pemilik</label>
        <input type="text" name="pemilik" class="form-control" maxlength="100" value="<?= e($edit['pemilik'] ?? '') ?>">
      </div>
      <div class="col-12 d-flex gap-2">
        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?><a href="kendaraan.php" class="btn btn-secondary">Batal</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<form method="get" class="input-group mb-3">
  <input type="text" name="cari" class="form-control" placeholder="Cari plat nomor atau pemilik..." value="<?= e($cari) ?>">
  <button class="btn btn-outline-secondary">Cari</button>
  <?php if ($cari !== ''): ?><a href="kendaraan.php" class="btn btn-outline-danger">Reset</a><?php endif; ?>
</form>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Plat Nomor</th><th>Jenis</th><th>Warna</th><th>Pemilik</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="fw-bold"><?= e($d['plat_nomor']) ?></td>
          <td><?= ucfirst(e($d['jenis_kendaraan'])) ?></td>
          <td><?= e($d['warna']) ?></td>
          <td><?= e($d['pemilik']) ?></td>
          <td><?= $d['sedang_parkir'] ? '<span class="badge text-bg-danger">Sedang parkir</span>' : '<span class="badge text-bg-secondary">Di luar</span>' ?></td>
          <td>
            <a href="kendaraan.php?edit=<?= $d['id_kendaraan'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus kendaraan ini?')">
              <input type="hidden" name="hapus" value="<?= $d['id_kendaraan'] ?>">
              <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?><tr><td colspan="7" class="text-center text-muted">Belum ada data kendaraan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
