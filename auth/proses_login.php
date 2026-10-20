<?php
require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/login.php');
}
csrf_cek();

$username = trim(isset($_POST['username']) ? $_POST['username'] : '');
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    flash_set('error', 'Username dan password wajib diisi.');
    redirect('auth/login.php');
}

$u = db_row($koneksi, 'SELECT * FROM user WHERE username = ? LIMIT 1', [$username]);

$cocok = false;
if ($u) {
    if (password_verify($password, $u['password'])) {
        $cocok = true;
        // Perbarui hash jika algoritmanya sudah usang
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            db_exec($koneksi, 'UPDATE user SET password = ? WHERE id_user = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$u['id_user']]);
        }
    } elseif (hash_equals($u['password'], $password)) {
        // Kompatibilitas: password lama yang masih tersimpan polos langsung di-hash
        $cocok = true;
        db_exec($koneksi, 'UPDATE user SET password = ? WHERE id_user = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$u['id_user']]);
    }
}

if (!$cocok) {
    flash_set('error', 'Username atau password salah.');
    redirect('auth/login.php');
}

session_regenerate_id(true);
$_SESSION['id_user']  = (int)$u['id_user'];
$_SESSION['nama']     = $u['nama'];
$_SESSION['username'] = $u['username'];
$_SESSION['level']    = $u['level'];

catat_log($koneksi, $u['nama'] . ' (' . $u['level'] . ') masuk ke sistem');
flash_set('success', 'Selamat datang, ' . $u['nama'] . '!');
redirect(dashboard_path($u['level']));
