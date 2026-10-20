<?php
require_once __DIR__ . '/../config/koneksi.php';

if (!empty($_SESSION['id_user'])) {
    catat_log($koneksi, $_SESSION['nama'] . ' (' . $_SESSION['level'] . ') keluar dari sistem');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();
flash_set('success', 'Anda sudah keluar. Sampai jumpa!');
redirect('auth/login.php');
