<?php
/**
 * config/koneksi.php
 * Koneksi database + fungsi bantu yang dipakai di seluruh aplikasi.
 */

// ---------- Pengaturan database ----------
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'peminjaman_alat';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><body style="font-family:sans-serif;padding:3rem;max-width:36rem;margin:auto">'
        . '<h2>Koneksi database gagal</h2>'
        . '<p>Pastikan MySQL aktif dan database <b>peminjaman_alat</b> sudah di-import dari '
        . '<code>database/peminjaman_alat.sql</code>.</p>'
        . '<p style="color:#888">' . htmlspecialchars($ex->getMessage()) . '</p></body></html>';
    exit;
}

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- URL dasar (otomatis, folder boleh di-rename) ----------
$__root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$__app  = realpath(__DIR__ . '/..');
$__base = '/peminjaman_alat';
if ($__root && $__app) {
    $__root = str_replace('\\', '/', $__root);
    $__app  = str_replace('\\', '/', $__app);
    if (strpos($__app, $__root) === 0) {
        $__base = substr($__app, strlen($__root));
    }
}
define('BASE_URL', rtrim($__base, '/'));

// ---------- Dasar ----------
function e($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function dashboard_path($level)
{
    return $level . '/dashboard.php';
}

// ---------- Flash message ----------
function flash_set($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_ambil()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_cek()
{
    $kirim = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!hash_equals(csrf_token(), $kirim)) {
        flash_set('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        $balik = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('index.php');
        header('Location: ' . $balik);
        exit;
    }
}

// ---------- Query helper (prepared statement) ----------
function db_query($k, $sql, $params = [])
{
    $st = $k->prepare($sql);
    if ($params) {
        $tipe = '';
        foreach ($params as $p) {
            $tipe .= is_int($p) ? 'i' : 's';
        }
        $st->bind_param($tipe, ...$params);
    }
    $st->execute();
    return $st;
}

function db_all($k, $sql, $params = [])
{
    $st = db_query($k, $sql, $params);
    $hasil = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $hasil;
}

function db_row($k, $sql, $params = [])
{
    $rows = db_all($k, $sql, $params);
    return $rows ? $rows[0] : null;
}

function db_val($k, $sql, $params = [])
{
    $row = db_row($k, $sql, $params);
    return $row ? array_values($row)[0] : null;
}

function db_exec($k, $sql, $params = [])
{
    $st = db_query($k, $sql, $params);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}

// ---------- Log aktivitas ----------
function catat_log($k, $aktivitas)
{
    db_exec($k, 'INSERT INTO log_aktivitas (aktivitas, tanggal) VALUES (?, CURDATE())', [$aktivitas]);
}

// ---------- Format ----------
function tgl($tanggal)
{
    if (!$tanggal) {
        return '-';
    }
    $bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function inisial($nama)
{
    $kata = preg_split('/\s+/', trim($nama));
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil ?: '?';
}

function hari_selisih($dari, $sampai)
{
    return (int)floor((strtotime($sampai) - strtotime($dari)) / 86400);
}

/**
 * Status gabungan peminjaman.
 * persetujuan: 0 menunggu, 1 disetujui, 2 ditolak
 * status     : 0 belum dipinjam, 1 sedang dipinjam, 2 sudah dikembalikan
 * return [label, nada]
 */
function status_info($r)
{
    if ((int)$r['persetujuan'] === 0) {
        return ['Menunggu persetujuan', 'amber'];
    }
    if ((int)$r['persetujuan'] === 2) {
        return ['Ditolak', 'red'];
    }
    if ((int)$r['status'] === 2) {
        return ['Dikembalikan', 'green'];
    }
    if ($r['tanggal_kembali'] < date('Y-m-d')) {
        return ['Terlambat', 'red'];
    }
    return ['Sedang dipinjam', 'blue'];
}

function badge($label, $nada = 'gray')
{
    return '<span class="badge badge-' . e($nada) . '"><i></i>' . e($label) . '</span>';
}

function badge_status($r)
{
    $s = status_info($r);
    return badge($s[0], $s[1]);
}

function badge_level($level)
{
    $map = ['admin' => 'violet', 'petugas' => 'blue', 'peminjam' => 'green'];
    return badge(ucfirst($level), isset($map[$level]) ? $map[$level] : 'gray');
}

function badge_kondisi($kondisi)
{
    $k = strtolower($kondisi);
    $nada = 'green';
    if (strpos($k, 'ringan') !== false) {
        $nada = 'amber';
    } elseif (strpos($k, 'berat') !== false || strpos($k, 'rusak') !== false) {
        $nada = 'red';
    }
    return badge($kondisi, $nada);
}

// ---------- Tampilan kategori ----------
function kategori_ikon($nama)
{
    $n = strtolower($nama);
    if (strpos($n, 'elektro') !== false) return 'monitor';
    if (strpos($n, 'multi') !== false) return 'camera';
    if (strpos($n, 'praktik') !== false || strpos($n, 'bengkel') !== false) return 'tool';
    if (strpos($n, 'lab') !== false) return 'flask';
    if (strpos($n, 'olahraga') !== false) return 'ball';
    return 'box';
}

function kategori_gradien($id)
{
    // Gradien biru bergeser per kategori (dipakai sebagai latar ikon).
    $g = [
        ['#8EC0FF', '#3382F5'], ['#7DE3FF', '#3382F5'], ['#A5B4FC', '#4F6BED'],
        ['#93C5FD', '#1F63DB'], ['#67D5F5', '#1A78C2'], ['#BCD9FF', '#5AA0FF'],
    ];
    $c = $g[((int)$id) % count($g)];
    return 'background:linear-gradient(135deg,' . $c[0] . ',' . $c[1] . ')';
}

/** Logo bintang untuk halaman tanpa navbar (login). */
function logo_svg_login($kelas = 'h-10 w-10')
{
    return '<span class="logo-kilau grid place-items-center rounded-xl bg-gradient-to-br from-accent-300 to-accent-600 text-white shadow-glow ' . e($kelas) . '">'
        . '<svg viewBox="0 0 24 24" class="h-[62%] w-[62%]" fill="currentColor"><path d="M12 2c.8 5.4 3.6 8.2 10 10-6.4 1.8-9.2 4.6-10 10-.8-5.4-3.6-8.2-10-10 6.4-1.8 9.2-4.6 10-10z"/></svg></span>';
}

/** Maskot "Pinja-chan": roh biru kecil yang berkedip dan melambai. */
function maskot($kelas = 'w-56 h-56')
{
    static $n = 0;
    $i = 'm' . (++$n);
    return '<svg viewBox="0 0 200 210" class="' . e($kelas) . '" role="img" aria-label="Maskot Pinja-chan">'
        . '<defs>'
        . '<linearGradient id="' . $i . 'b" x1="0" y1="0" x2="0.8" y2="1"><stop offset="0" stop-color="#BCD9FF"/><stop offset="1" stop-color="#3382F5"/></linearGradient>'
        . '<radialGradient id="' . $i . 'g" cx="0.5" cy="0.5" r="0.5"><stop offset="0" stop-color="#7DE3FF" stop-opacity=".55"/><stop offset="1" stop-color="#7DE3FF" stop-opacity="0"/></radialGradient>'
        . '</defs>'
        . '<circle cx="100" cy="104" r="96" fill="url(#' . $i . 'g)"/>'
        . '<ellipse class="bayangan" cx="100" cy="196" rx="46" ry="7" fill="#1F63DB" opacity=".18"/>'
        . '<g class="melayang">'
        . '<path class="bintang" d="M100 2c1.4 8 4 10.6 12 12-8 1.4-10.6 4-12 12-1.4-8-4-10.6-12-12 8-1.4 10.6-4 12-12z" fill="#fff" stroke="#7DE3FF" stroke-width="1.5"/>'
        . '<path d="M100 26V16" stroke="#3382F5" stroke-width="3" stroke-linecap="round"/>'
        . '<path d="M100 34c-38 0-62 26-62 62 0 40 24 82 62 82s62-42 62-82c0-36-24-62-62-62z" fill="url(#' . $i . 'b)"/>'
        . '<ellipse cx="72" cy="58" rx="18" ry="8" fill="#fff" opacity=".5" transform="rotate(-28 72 58)"/>'
        . '<g class="lambai"><ellipse cx="164" cy="128" rx="10" ry="7" fill="#8EC0FF" transform="rotate(-30 164 128)"/></g>'
        . '<ellipse cx="38" cy="132" rx="10" ry="7" fill="#8EC0FF" transform="rotate(20 38 132)"/>'
        . '<g class="kedip"><ellipse cx="78" cy="104" rx="12" ry="16" fill="#12204A"/><circle cx="82" cy="97" r="5" fill="#fff"/><circle cx="74" cy="110" r="2.2" fill="#fff"/></g>'
        . '<g class="kedip"><ellipse cx="122" cy="104" rx="12" ry="16" fill="#12204A"/><circle cx="126" cy="97" r="5" fill="#fff"/><circle cx="118" cy="110" r="2.2" fill="#fff"/></g>'
        . '<ellipse cx="57" cy="124" rx="9" ry="5" fill="#FF9EC4" opacity=".55"/><ellipse cx="143" cy="124" rx="9" ry="5" fill="#FF9EC4" opacity=".55"/>'
        . '<path d="M91 126q9 9 18 0" stroke="#12204A" stroke-width="3.2" fill="none" stroke-linecap="round"/>'
        . '</g></svg>';
}

// ---------- Ikon (SVG inline) ----------
function icon($nama, $kelas = 'w-5 h-5')
{
    static $d = [
        'home' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
        'users' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        'user' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'tag' => 'M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01',
        'box' => 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.27 6.96L12 12.01l8.73-5.05 M12 22.08V12',
        'clipboard' => 'M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2 M9 2h6a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z M9 12h6 M9 16h4',
        'return' => 'M9 10l-5 5 5 5 M20 4v7a4 4 0 0 1-4 4H4',
        'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
        'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
        'menu' => 'M3 12h18 M3 6h18 M3 18h18',
        'x' => 'M18 6L6 18 M6 6l12 12',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35',
        'plus' => 'M12 5v14 M5 12h14',
        'edit' => 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z',
        'trash' => 'M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2',
        'check' => 'M20 6L9 17l-5-5',
        'clock' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M12 6v6l4 2',
        'calendar' => 'M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z M16 2v4 M8 2v4 M3 10h18',
        'printer' => 'M6 9V2h12v7 M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2 M6 14h12v8H6z',
        'arrow' => 'M5 12h14 M12 5l7 7-7 7',
        'camera' => 'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'monitor' => 'M20 3H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z M8 21h8 M12 17v4',
        'tool' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z',
        'flask' => 'M9 3h6 M10 3v6.5L4.5 19a2 2 0 0 0 1.7 3h11.6a2 2 0 0 0 1.7-3L14 9.5V3 M7.5 15h9',
        'ball' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M2 12h20 M12 2a15 15 0 0 1 0 20 M12 2a15 15 0 0 0 0 20',
        'zap' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
        'shield' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'alert' => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z M12 9v4 M12 17h.01',
        'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z',
        'lock' => 'M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2z M7 11V7a5 5 0 0 1 10 0v4',
        'file' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8',
        'chevron' => 'M6 9l6 6 6-6',
        'eye' => 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'trend' => 'M23 6l-9.5 9.5-5-5L1 18 M17 6h6v6',
        'ban' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M4.93 4.93l14.14 14.14',
        'mail' => 'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z M22 6l-10 7L2 6',
        'phone' => 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z',
        'map' => 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
    ];
    $path = isset($d[$nama]) ? $d[$nama] : $d['box'];
    return '<svg class="' . e($kelas) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $path . '"/></svg>';
}

// ---------- Proses bisnis bersama (dipakai admin & petugas) ----------

/**
 * Setujui / tolak pengajuan peminjaman.
 * return [berhasil(bool), pesan]
 */
function proses_persetujuan($k, $id_peminjaman, $aksi, $nama_petugas)
{
    $k->begin_transaction();
    try {
        $p = db_row(
            $k,
            'SELECT p.*, a.nama_alat, a.stok, u.nama AS nama_peminjam
             FROM peminjaman p
             JOIN alat a ON a.id_alat = p.id_alat
             JOIN user u ON u.id_user = p.id_user
             WHERE p.id_peminjaman = ? FOR UPDATE',
            [(int)$id_peminjaman]
        );
        if (!$p) {
            $k->rollback();
            return [false, 'Data peminjaman tidak ditemukan.'];
        }
        if ((int)$p['persetujuan'] !== 0) {
            $k->rollback();
            return [false, 'Pengajuan ini sudah diproses sebelumnya.'];
        }

        if ($aksi === 'setujui') {
            if ((int)$p['stok'] < 1) {
                $k->rollback();
                return [false, 'Stok ' . $p['nama_alat'] . ' habis, pengajuan tidak bisa disetujui.'];
            }
            db_exec($k, 'UPDATE alat SET stok = stok - 1 WHERE id_alat = ?', [(int)$p['id_alat']]);
            db_exec($k, 'UPDATE peminjaman SET persetujuan = 1, status = 1 WHERE id_peminjaman = ?', [(int)$id_peminjaman]);
            catat_log($k, $nama_petugas . ' menyetujui peminjaman ' . $p['nama_alat'] . ' oleh ' . $p['nama_peminjam']);
            $k->commit();
            return [true, 'Peminjaman ' . $p['nama_alat'] . ' oleh ' . $p['nama_peminjam'] . ' disetujui.'];
        }

        db_exec($k, 'UPDATE peminjaman SET persetujuan = 2, status = 0 WHERE id_peminjaman = ?', [(int)$id_peminjaman]);
        catat_log($k, $nama_petugas . ' menolak peminjaman ' . $p['nama_alat'] . ' oleh ' . $p['nama_peminjam']);
        $k->commit();
        return [true, 'Pengajuan ' . $p['nama_alat'] . ' oleh ' . $p['nama_peminjam'] . ' ditolak.'];
    } catch (Throwable $ex) {
        $k->rollback();
        return [false, 'Terjadi kesalahan saat memproses pengajuan.'];
    }
}

/**
 * Catat pengembalian alat.
 * return [berhasil(bool), pesan]
 */
function proses_pengembalian($k, $id_peminjaman, $nama_petugas)
{
    $k->begin_transaction();
    try {
        $p = db_row(
            $k,
            'SELECT p.*, a.nama_alat, u.nama AS nama_peminjam
             FROM peminjaman p
             JOIN alat a ON a.id_alat = p.id_alat
             JOIN user u ON u.id_user = p.id_user
             WHERE p.id_peminjaman = ? FOR UPDATE',
            [(int)$id_peminjaman]
        );
        if (!$p || (int)$p['persetujuan'] !== 1 || (int)$p['status'] !== 1) {
            $k->rollback();
            return [false, 'Peminjaman ini tidak sedang berjalan.'];
        }
        db_exec(
            $k,
            'INSERT INTO pengembalian (id_alat, id_user, id_peminjaman, tanggal_kembali) VALUES (?, ?, ?, CURDATE())',
            [(int)$p['id_alat'], (int)$p['id_user'], (int)$id_peminjaman]
        );
        db_exec($k, 'UPDATE peminjaman SET status = 2 WHERE id_peminjaman = ?', [(int)$id_peminjaman]);
        db_exec($k, 'UPDATE alat SET stok = stok + 1 WHERE id_alat = ?', [(int)$p['id_alat']]);
        catat_log($k, $nama_petugas . ' menerima pengembalian ' . $p['nama_alat'] . ' dari ' . $p['nama_peminjam']);
        $k->commit();
        return [true, $p['nama_alat'] . ' dari ' . $p['nama_peminjam'] . ' sudah dikembalikan.'];
    } catch (Throwable $ex) {
        $k->rollback();
        return [false, 'Terjadi kesalahan saat mencatat pengembalian.'];
    }
}

// ---------- Komponen tampilan peminjam ----------

/** Alat boleh diajukan bila stok ada dan tidak rusak berat. */
function alat_bisa_dipinjam($a)
{
    return (int)$a['stok'] > 0 && stripos($a['kondisi'], 'berat') === false;
}

/** Kartu alat. Butuh kolom: id_alat, nama_alat, id_kategori, nama_kategori, stok, kondisi */
function kartu_alat($a, $tunda = 0)
{
    $bisa = alat_bisa_dipinjam($a);
    $ikon = kategori_ikon($a['nama_kategori']);
    ob_start(); ?>
    <article class="card flex flex-col p-4">
        <div class="flex items-start justify-between gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-2xl text-white shadow-glow" style="<?= kategori_gradien($a['id_kategori']) ?>"><?= icon($ikon, 'w-5 h-5') ?></span>
            <?php if ($bisa): ?>
                <span class="badge badge-green"><i></i>Tersedia <?= (int)$a['stok'] ?></span>
            <?php else: ?>
                <span class="badge badge-red"><i></i><?= (int)$a['stok'] < 1 ? 'Stok habis' : 'Tidak layak pakai' ?></span>
            <?php endif; ?>
        </div>
        <p class="mt-4 text-xs font-bold text-accent-600"><?= e($a['nama_kategori']) ?></p>
        <h3 class="mt-0.5 text-base font-black leading-snug text-zinc-900"><?= e($a['nama_alat']) ?></h3>
        <div class="mt-2"><?= badge_kondisi($a['kondisi']) ?></div>
        <div class="mt-auto pt-5">
            <?php if ($bisa): ?>
                <a href="<?= url('peminjam/pinjam.php?id=' . (int)$a['id_alat']) ?>" class="btn btn-primary w-full">Pinjam alat ini</a>
            <?php else: ?>
                <span class="btn w-full cursor-not-allowed bg-zinc-100 text-zinc-400">Belum bisa dipinjam</span>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
