<?php
// Titik masuk aplikasi: arahkan ke halaman login (atau dashboard bila sudah masuk).
require_once __DIR__ . '/config/koneksi.php';

if (!empty($_SESSION['id_user']) && !empty($_SESSION['level'])) {
    redirect(dashboard_path($_SESSION['level']));
}
redirect('auth/login.php');
