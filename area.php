<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['admin']);
$judul = 'Kelola Area Parkir';

// ---------- HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    try {
        eksekusi('DELETE FROM tb_area_parkir WHERE id_area = ?', 'i', [$id]);
        catat_log('Hapus area ID ' . $id);
        flash('Area parkir berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        flash('Area tidak bisa dihapus karena sudah dipakai data transaksi.', 'danger');
    }
    header('Location: area.php');
    exit;
}

// ---------- SIMPAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id        = (int)$_POST['id_area'];
    $nama      = trim($_POST['nama_area']);
    $kapasitas = (int)$_POST['kapasitas'];
    $lama      = $id > 0 ? ambil_satu('SELECT terisi FROM tb_area_parkir WHERE id_area = ?', 'i', [$id]) : null;

    if ($nama === '' || $kapasitas <= 0) {
        flash('Nama area dan kapasitas (lebih dari 0) wajib diisi.', 'danger');
    } elseif ($lama && $kapasitas < (int)$lama['terisi']) {
        flash('Kapasitas tidak boleh lebih kecil dari jumlah kendaraan yang sedang parkir (' . $lama['terisi'] . ').', 'danger');
    } elseif ($id > 0) {
        eksekusi('UPDATE tb_area_parkir SET nama_area = ?, kapasitas = ? WHERE id_area = ?', 'sii', [$nama, $kapasitas, $id]);
        catat_log('Ubah area ' . $nama);
        flash('Area parkir berhasil diubah.');
    } else {
        eksekusi('INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES (?, ?, 0)', 'si', [$nama, $kapasitas]);
        catat_log('Tambah area ' . $nama);
        flash('Area parkir baru berhasil ditambahkan.');
    }
    header('Location: area.php');
    exit;
}

// ---------- BACA ----------
$edit = isset($_GET['edit']) ? ambil_satu('SELECT * FROM tb_area_parkir WHERE id_area = ?', 'i', [(int)$_GET['edit']]) : null;
$data = ambil('SELECT * FROM tb_area_parkir ORDER BY id_area');

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Kelola Area Parkir</h3>

<div class="card mb-4">
  <div class="card-header"><?= $edit ? 'Edit Area' : 'Tambah Area' ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id_area" value="<?= $edit['id_area'] ?? 0 ?>">
      <div class="col-md-5">
        <label class="form-label">Nama Area</label>
        <input type="text" name="nama_area" class="form-control" maxlength="50" value="<?= e($edit['nama_area'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Kapasitas</label>
        <input type="number" name="kapasitas" class="form-control" min="1" value="<?= e($edit['kapasitas'] ?? '') ?>" required>
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?><a href="area.php" class="btn btn-secondary">Batal</a><?php endif; ?>
      </div>
    </form>
    <div class="form-text mt-2">Kolom "Terisi" diperbarui otomatis saat kendaraan masuk dan keluar.</div>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Nama Area</th><th>Kapasitas</th><th style="width:30%">Terisi</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d):
            $persen = $d['kapasitas'] > 0 ? round($d['terisi'] / $d['kapasitas'] * 100) : 0; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($d['nama_area']) ?></td>
          <td><?= $d['kapasitas'] ?></td>
          <td>
            <div class="progress" role="progressbar">
              <div class="progress-bar <?= $persen >= 90 ? 'bg-danger' : 'bg-success' ?>" style="width:<?= $persen ?>%"><?= $d['terisi'] ?>/<?= $d['kapasitas'] ?></div>
            </div>
          </td>
          <td>
            <a href="area.php?edit=<?= $d['id_area'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus area ini?')">
              <input type="hidden" name="hapus" value="<?= $d['id_area'] ?>">
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
