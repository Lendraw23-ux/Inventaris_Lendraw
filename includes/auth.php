<?php
/**
 * includes/auth.php
 * Cek session & level. Sebelum di-include, halaman boleh mengisi:
 *   $level_boleh = ['admin'];            // level yang diizinkan
 * Jika kosong, cukup login saja.
 */
require_once __DIR__ . '/../config/koneksi.php';

if (empty($_SESSION['id_user'])) {
    flash_set('error', 'Silakan masuk terlebih dahulu.');
    redirect('auth/login.php');
}

$user = [
    'id'       => (int)$_SESSION['id_user'],
    'nama'     => $_SESSION['nama'],
    'username' => $_SESSION['username'],
    'level'    => $_SESSION['level'],
];

if (isset($level_boleh)) {
    $level_boleh = (array)$level_boleh;
    if (!in_array($user['level'], $level_boleh, true)) {
        flash_set('error', 'Anda tidak punya akses ke halaman tersebut.');
        redirect(dashboard_path($user['level']));
    }
}
