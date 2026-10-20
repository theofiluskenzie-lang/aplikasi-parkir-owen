<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['petugas']);
$judul = 'Transaksi Parkir';
$id_petugas = (int)$_SESSION['user']['id_user'];

// ---------- KENDARAAN MASUK ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['masuk'])) {
    $id_kendaraan = (int)$_POST['id_kendaraan'];
    $id_area      = (int)$_POST['id_area'];

    $kendaraan = ambil_satu('SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?', 'i', [$id_kendaraan]);
    $area      = ambil_satu('SELECT * FROM tb_area_parkir WHERE id_area = ?', 'i', [$id_area]);
    $sedang    = ambil_satu("SELECT id_parkir FROM tb_transaksi WHERE id_kendaraan = ? AND status = 'masuk'", 'i', [$id_kendaraan]);
    $tarif     = $kendaraan ? ambil_satu('SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = ?', 's', [$kendaraan['jenis_kendaraan']]) : null;

    if (!$kendaraan || !$area) {
        flash('Kendaraan atau area parkir tidak valid.', 'danger');
    } elseif ($sedang) {
        flash('Kendaraan ini masih tercatat sedang parkir.', 'danger');
    } elseif ((int)$area['terisi'] >= (int)$area['kapasitas']) {
        flash('Area ' . $area['nama_area'] . ' sudah penuh.', 'danger');
    } elseif (!$tarif) {
        flash('Belum ada tarif untuk jenis kendaraan "' . $kendaraan['jenis_kendaraan'] . '". Hubungi admin.', 'danger');
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            eksekusi("INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area) VALUES (?, NOW(), ?, 'masuk', ?, ?)",
                     'iiii', [$id_kendaraan, $tarif['id_tarif'], $id_petugas, $id_area]);
            eksekusi('UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = ?', 'i', [$id_area]);
            mysqli_commit($koneksi);
            catat_log('Kendaraan masuk: ' . $kendaraan['plat_nomor'] . ' (' . $area['nama_area'] . ')');
            flash('Kendaraan ' . $kendaraan['plat_nomor'] . ' berhasil dicatat masuk di ' . $area['nama_area'] . '.');
        } catch (mysqli_sql_exception $ex) {
            mysqli_rollback($koneksi);
            flash('Gagal menyimpan transaksi masuk.', 'danger');
        }
    }
    header('Location: transaksi.php');
    exit;
}

// ---------- KENDARAAN KELUAR (hitung durasi & biaya) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keluar'])) {
    $id  = (int)$_POST['keluar'];
    $trx = ambil_satu("SELECT t.id_area, k.plat_nomor FROM tb_transaksi t
                       JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
                       WHERE t.id_parkir = ? AND t.status = 'masuk'", 'i', [$id]);
    if (!$trx) {
        flash('Transaksi tidak ditemukan atau kendaraan sudah keluar.', 'danger');
        header('Location: transaksi.php');
        exit;
    }

    mysqli_begin_transaction($koneksi);
    try {
        // Durasi dibulatkan ke atas per jam (minimal 1 jam), biaya = durasi x tarif per jam
        eksekusi("UPDATE tb_transaksi t
                  JOIN tb_tarif f ON f.id_tarif = t.id_tarif
                  SET t.waktu_keluar = NOW(),
                      t.durasi_jam   = GREATEST(1, CEIL(TIMESTAMPDIFF(MINUTE, t.waktu_masuk, NOW()) / 60)),
                      t.biaya_total  = GREATEST(1, CEIL(TIMESTAMPDIFF(MINUTE, t.waktu_masuk, NOW()) / 60)) * f.tarif_per_jam,
                      t.status       = 'keluar'
                  WHERE t.id_parkir = ? AND t.status = 'masuk'", 'i', [$id]);
        eksekusi('UPDATE tb_area_parkir SET terisi = GREATEST(terisi - 1, 0) WHERE id_area = ?', 'i', [$trx['id_area']]);
        mysqli_commit($koneksi);
        catat_log('Kendaraan keluar: ' . $trx['plat_nomor'] . ' (transaksi #' . $id . ')');
        header('Location: cetak_struk.php?id=' . $id);   // langsung ke struk
        exit;
    } catch (mysqli_sql_exception $ex) {
        mysqli_rollback($koneksi);
        flash('Gagal memproses kendaraan keluar.', 'danger');
        header('Location: transaksi.php');
        exit;
    }
}

// ---------- BACA ----------
$kendaraan_bebas = ambil("SELECT k.* FROM tb_kendaraan k
                          WHERE NOT EXISTS (SELECT 1 FROM tb_transaksi t WHERE t.id_kendaraan = k.id_kendaraan AND t.status = 'masuk')
                          ORDER BY k.plat_nomor");
$area_tersedia = ambil('SELECT * FROM tb_area_parkir WHERE terisi < kapasitas ORDER BY nama_area');

$dasar = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area
          FROM tb_transaksi t
          JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
          JOIN tb_area_parkir a ON a.id_area = t.id_area ";
$sedang_parkir = ambil($dasar . "WHERE t.status = 'masuk' ORDER BY t.waktu_masuk");
$riwayat       = ambil($dasar . "WHERE t.status = 'keluar' ORDER BY t.waktu_keluar DESC LIMIT 15");

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Transaksi Parkir</h3>

<div class="row g-4">
  <!-- Form kendaraan masuk -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-success text-white">Kendaraan Masuk</div>
      <div class="card-body">
        <?php if (!$kendaraan_bebas): ?>
          <div class="alert alert-warning mb-0">Tidak ada kendaraan terdaftar yang bisa masuk. Minta admin menambahkan data kendaraan.</div>
        <?php elseif (!$area_tersedia): ?>
          <div class="alert alert-danger mb-0">Semua area parkir penuh.</div>
        <?php else: ?>
          <form method="post">
            <label class="form-label">Kendaraan</label>
            <select name="id_kendaraan" class="form-select mb-3" required>
              <?php foreach ($kendaraan_bebas as $k): ?>
                <option value="<?= $k['id_kendaraan'] ?>"><?= e($k['plat_nomor']) ?> - <?= e($k['jenis_kendaraan']) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="form-label">Area Parkir</label>
            <select name="id_area" class="form-select mb-3" required>
              <?php foreach ($area_tersedia as $a): ?>
                <option value="<?= $a['id_area'] ?>"><?= e($a['nama_area']) ?> (sisa <?= $a['kapasitas'] - $a['terisi'] ?>)</option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="masuk" class="btn btn-success w-100">Catat Masuk</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <!-- Sedang parkir -->
    <div class="card mb-4">
      <div class="card-header bg-danger text-white">Sedang Parkir (<?= count($sedang_parkir) ?>)</div>
      <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-dark">
            <tr><th>Plat</th><th>Jenis</th><th>Area</th><th>Masuk</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sedang_parkir as $d): ?>
            <tr>
              <td class="fw-bold"><?= e($d['plat_nomor']) ?></td>
              <td><?= e($d['jenis_kendaraan']) ?></td>
              <td><?= e($d['nama_area']) ?></td>
              <td><?= tgl($d['waktu_masuk']) ?></td>
              <td>
                <a href="cetak_struk.php?id=<?= $d['id_parkir'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Karcis</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Proses kendaraan <?= e($d['plat_nomor']) ?> keluar?')">
                  <input type="hidden" name="keluar" value="<?= $d['id_parkir'] ?>">
                  <button class="btn btn-sm btn-danger">Keluar</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$sedang_parkir): ?><tr><td colspan="5" class="text-center text-muted">Tidak ada kendaraan yang sedang parkir.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Riwayat -->
    <div class="card">
      <div class="card-header">Riwayat Terakhir</div>
      <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-dark">
            <tr><th>Plat</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Biaya</th><th>Struk</th></tr>
          </thead>
          <tbody>
            <?php foreach ($riwayat as $d): ?>
            <tr>
              <td class="fw-bold"><?= e($d['plat_nomor']) ?></td>
              <td><?= tgl($d['waktu_masuk']) ?></td>
              <td><?= tgl($d['waktu_keluar']) ?></td>
              <td><?= $d['durasi_jam'] ?> jam</td>
              <td><?= rupiah($d['biaya_total']) ?></td>
              <td><a href="cetak_struk.php?id=<?= $d['id_parkir'] ?>" target="_blank" class="btn btn-sm btn-primary">Cetak</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$riwayat): ?><tr><td colspan="6" class="text-center text-muted">Belum ada transaksi selesai.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
