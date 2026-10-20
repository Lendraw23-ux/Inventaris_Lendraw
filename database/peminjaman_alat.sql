CREATE DATABASE IF NOT EXISTS peminjaman_alat;
USE peminjaman_alat;

CREATE TABLE user (
  id_user INT(11) NOT NULL AUTO_INCREMENT,
  nama VARCHAR(255) NOT NULL,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  level ENUM('admin','petugas','peminjam') NOT NULL,
  PRIMARY KEY (id_user)
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id_kategori INT(11) NOT NULL AUTO_INCREMENT,
  nama_kategori VARCHAR(255) NOT NULL,
  PRIMARY KEY (id_kategori)
) ENGINE=InnoDB;

CREATE TABLE alat (
  id_alat INT(11) NOT NULL AUTO_INCREMENT,
  nama_alat VARCHAR(255) NOT NULL,
  id_kategori INT(11) NOT NULL,
  stok INT(11) NOT NULL,
  kondisi VARCHAR(255) NOT NULL,
  PRIMARY KEY (id_alat),
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
) ENGINE=InnoDB;

CREATE TABLE log_aktivitas (
  id_log INT(11) NOT NULL AUTO_INCREMENT,
  aktivitas TEXT NOT NULL,
  tanggal DATE NOT NULL,
  PRIMARY KEY (id_log)
) ENGINE=InnoDB;

CREATE TABLE peminjaman (
  id_peminjaman INT(11) NOT NULL AUTO_INCREMENT,
  id_user INT(11) NOT NULL,
  id_alat INT(11) NOT NULL,
  tanggal_pinjam DATE NOT NULL,
  tanggal_kembali DATE NOT NULL,
  status INT(11) NOT NULL,
  persetujuan INT(11) NOT NULL,
  PRIMARY KEY (id_peminjaman),
  FOREIGN KEY (id_user) REFERENCES user(id_user),
  FOREIGN KEY (id_alat) REFERENCES alat(id_alat)
) ENGINE=InnoDB;

CREATE TABLE pengembalian (
  id_pengembalian INT(11) NOT NULL AUTO_INCREMENT,
  id_alat INT(11) NOT NULL,
  id_user INT(11) NOT NULL,
  id_peminjaman INT(11) NOT NULL,
  tanggal_kembali DATE NOT NULL,
  PRIMARY KEY (id_pengembalian),
  FOREIGN KEY (id_alat) REFERENCES alat(id_alat),
  FOREIGN KEY (id_user) REFERENCES user(id_user),
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman)
) ENGINE=InnoDB;

-- =====================================================
-- DATA AWAL (contoh) - struktur tabel di atas tidak diubah
-- Akun login:
--   admin    / admin123
--   petugas  / petugas123
--   peminjam / peminjam123
-- Keterangan kolom di tabel peminjaman:
--   persetujuan : 0 = menunggu, 1 = disetujui, 2 = ditolak
--   status      : 0 = belum dipinjam, 1 = sedang dipinjam, 2 = sudah dikembalikan
-- =====================================================

INSERT INTO user (nama, username, password, level) VALUES
('Administrator', 'admin',    '$2y$10$jBbKtRdsPhXmsSjPhLu1Oeqm8BVf/SbgnWl8xMZBI08OAaPTyDypy', 'admin'),
('Budi Petugas',  'petugas',  '$2y$10$680uetDLvjbTOJO8rtgdceHA/LMU8bZu8id2XfnhP0jRSe/yOxB1W', 'petugas'),
('Sinta Rahma',   'peminjam', '$2y$10$WD0E/da53LZ00FFp49NjMePklP9dhXOQn94N/QKk5Mh/nROsnj9oK', 'peminjam'),
('Raka Pratama',  'raka',     '$2y$10$WD0E/da53LZ00FFp49NjMePklP9dhXOQn94N/QKk5Mh/nROsnj9oK', 'peminjam');

INSERT INTO kategori (nama_kategori) VALUES
('Elektronik'), ('Multimedia'), ('Alat Praktik'), ('Laboratorium'), ('Olahraga');

INSERT INTO alat (nama_alat, id_kategori, stok, kondisi) VALUES
('Laptop Asus Vivobook', 1, 8, 'Baik'),
('Proyektor Epson', 1, 3, 'Baik'),
('Kabel HDMI 5 Meter', 1, 12, 'Baik'),
('Kamera DSLR Canon', 2, 2, 'Baik'),
('Tripod Profesional', 2, 4, 'Baik'),
('Mikrofon Wireless', 2, 5, 'Rusak Ringan'),
('Bor Listrik', 3, 4, 'Baik'),
('Toolkit Set Lengkap', 3, 6, 'Baik'),
('Multimeter Digital', 4, 7, 'Baik'),
('Mikroskop Binokuler', 4, 3, 'Baik'),
('Bola Voli', 5, 10, 'Baik'),
('Net Badminton', 5, 0, 'Rusak Berat');

INSERT INTO peminjaman (id_user, id_alat, tanggal_pinjam, tanggal_kembali, status, persetujuan) VALUES
(3, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  DATE_SUB(CURDATE(), INTERVAL 1 DAY),  1, 1),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY),  2, 1),
(4, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0, 0),
(4, 9, DATE_SUB(CURDATE(), INTERVAL 2 DAY),  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  1, 1),
(3, 7, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 0, 2);

INSERT INTO pengembalian (id_alat, id_user, id_peminjaman, tanggal_kembali) VALUES
(4, 3, 2, DATE_SUB(CURDATE(), INTERVAL 8 DAY));

INSERT INTO log_aktivitas (aktivitas, tanggal) VALUES
('Sistem diinstal dan data awal dimuat', CURDATE()),
('Sinta Rahma mengajukan peminjaman Proyektor Epson', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
('Budi Petugas menyetujui peminjaman Proyektor Epson', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
('Budi Petugas menerima pengembalian Kamera DSLR Canon dari Sinta Rahma', DATE_SUB(CURDATE(), INTERVAL 8 DAY));
