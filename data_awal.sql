--   User    / Password
--   admin   / admin123
--   petugas / petugas123
--   owner   / owner123

USE parkir;

INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES
    ('Administrator', 'admin',   'admin123',   'admin',   1),
    ('Petugas Satu',  'petugas', 'petugas123', 'petugas', 1),
    ('Pemilik Parkir', 'owner',  'owner123',   'owner',   1);

INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES
    ('motor', 2000),
    ('mobil', 5000),
    ('lainnya', 3000);

INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES
    ('Area A (Motor)', 20, 0),
    ('Area B (Mobil)', 10, 0);

INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES
    ('BP 1234 AB', 'motor', 'Hitam',  'Owen',  1),
    ('BP 5678 CD', 'mobil', 'Putih',  'Budi',  1),
    ('BP 9012 EF', 'motor', 'Merah',  'Rani',  1);
