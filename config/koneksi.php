<?php
// ============================================================
// config/koneksi.php - koneksi database + fungsi bantu
// Dipanggil paling atas setiap halaman:
//   require __DIR__ . '/config/koneksi.php';
// ============================================================
session_start();

// Supaya error database selalu menjadi exception (berlaku di semua versi PHP)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'parkir';        // ganti jadi 'db_parkir' kalau database kamu bernama itu

try {
    $koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    mysqli_set_charset($koneksi, 'utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('Koneksi database gagal: ' . $ex->getMessage() .
        '. Pastikan MySQL di XAMPP sudah Start dan nama database benar.');
}

define('BASE_URL', '/parkir/');   // nama folder proyek di htdocs

// ---------- Fungsi bantu database (semua memakai prepared statement) ----------
function siapkan($sql, $types = '', $params = []) {
    global $koneksi;
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

// SELECT banyak baris -> array
function ambil($sql, $types = '', $params = []) {
    $stmt = siapkan($sql, $types, $params);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

// SELECT satu baris -> array atau null
function ambil_satu($sql, $types = '', $params = []) {
    $baris = ambil($sql, $types, $params);
    return $baris ? $baris[0] : null;
}

// SELECT satu nilai (misal COUNT) -> nilai kolom pertama
function skalar($sql, $types = '', $params = []) {
    $stmt = siapkan($sql, $types, $params);
    $baris = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
    return $baris ? $baris[0] : 0;
}

// INSERT / UPDATE / DELETE -> jumlah baris yang terpengaruh
function eksekusi($sql, $types = '', $params = []) {
    $stmt = siapkan($sql, $types, $params);
    return mysqli_stmt_affected_rows($stmt);
}

// ---------- Fungsi bantu umum ----------
function e($teks) {                       // cegah XSS saat menampilkan data
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tgl($waktu) {
    return $waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-';
}

function flash($pesan, $tipe = 'success') {   // pesan sekali tampil
    $_SESSION['flash'] = [$tipe, $pesan];
}

// Wajib login; $roles = daftar role yang boleh (kosong = semua role)
function wajib_login($roles = []) {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        die('<h3>Akses ditolak</h3><p>Halaman ini tidak tersedia untuk role Anda. ' .
            '<a href="' . BASE_URL . 'dashboard.php">Kembali ke dashboard</a></p>');
    }
}

// Catat aktivitas ke tb_log_aktivitas
function catat_log($aktivitas) {
    if (!isset($_SESSION['user'])) {
        return;
    }
    eksekusi('INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES (?, ?, NOW())',
             'is', [$_SESSION['user']['id_user'], mb_substr($aktivitas, 0, 100)]);
}
