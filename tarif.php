<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['admin']);
$judul = 'Kelola Tarif Parkir';
$daftar_jenis = ['motor', 'mobil', 'lainnya'];

// ---------- HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    try {
        eksekusi('DELETE FROM tb_tarif WHERE id_tarif = ?', 'i', [$id]);
        catat_log('Hapus tarif ID ' . $id);
        flash('Tarif berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        flash('Tarif tidak bisa dihapus karena sudah dipakai data transaksi.', 'danger');
    }
    header('Location: tarif.php');
    exit;
}

// ---------- SIMPAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id    = (int)$_POST['id_tarif'];
    $jenis = $_POST['jenis_kendaraan'];
    $tarif = (int)$_POST['tarif_per_jam'];

    $dobel = ambil_satu('SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = ? AND id_tarif <> ?', 'si', [$jenis, $id]);

    if (!in_array($jenis, $daftar_jenis) || $tarif <= 0) {
        flash('Jenis kendaraan dan tarif per jam (lebih dari 0) wajib diisi.', 'danger');
    } elseif ($dobel) {
        flash('Tarif untuk jenis kendaraan ini sudah ada. Silakan edit data yang lama.', 'danger');
    } elseif ($id > 0) {
        eksekusi('UPDATE tb_tarif SET jenis_kendaraan = ?, tarif_per_jam = ? WHERE id_tarif = ?', 'sii', [$jenis, $tarif, $id]);
        catat_log('Ubah tarif ' . $jenis . ' menjadi ' . $tarif);
        flash('Tarif berhasil diubah.');
    } else {
        eksekusi('INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES (?, ?)', 'si', [$jenis, $tarif]);
        catat_log('Tambah tarif ' . $jenis . ' ' . $tarif);
        flash('Tarif baru berhasil ditambahkan.');
    }
    header('Location: tarif.php');
    exit;
}

// ---------- BACA ----------
$edit = isset($_GET['edit']) ? ambil_satu('SELECT * FROM tb_tarif WHERE id_tarif = ?', 'i', [(int)$_GET['edit']]) : null;
$data = ambil('SELECT * FROM tb_tarif ORDER BY id_tarif');

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Kelola Tarif Parkir</h3>

<div class="card mb-4">
  <div class="card-header"><?= $edit ? 'Edit Tarif' : 'Tambah Tarif' ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id_tarif" value="<?= $edit['id_tarif'] ?? 0 ?>">
      <div class="col-md-4">
        <label class="form-label">Jenis Kendaraan</label>
        <select name="jenis_kendaraan" class="form-select">
          <?php foreach ($daftar_jenis as $j): ?>
            <option value="<?= $j ?>" <?= ($edit['jenis_kendaraan'] ?? '') === $j ? 'selected' : '' ?>><?= ucfirst($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tarif per Jam (Rp)</label>
        <input type="number" name="tarif_per_jam" class="form-control" min="1" value="<?= e($edit['tarif_per_jam'] ?? '') ?>" required>
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?><a href="tarif.php" class="btn btn-secondary">Batal</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Jenis Kendaraan</th><th>Tarif per Jam</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= ucfirst(e($d['jenis_kendaraan'])) ?></td>
          <td><?= rupiah($d['tarif_per_jam']) ?></td>
          <td>
            <a href="tarif.php?edit=<?= $d['id_tarif'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus tarif ini?')">
              <input type="hidden" name="hapus" value="<?= $d['id_tarif'] ?>">
              <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
