<?php
/**
 * includes/navbar.php
 * Satu navigasi atas untuk semua level (admin, petugas, peminjam).
 * Wajib setelah header.php dan auth.php.
 */
$menu_aktif = basename($_SERVER['SCRIPT_NAME'], '.php');

$menus = [
    'admin' => [
        ['dashboard', 'Dashboard', 'grid'],
        ['user', 'Pengguna', 'users'],
        ['kategori', 'Kategori', 'tag'],
        ['alat', 'Data Alat', 'box'],
        ['peminjaman', 'Peminjaman', 'clipboard'],
        ['pengembalian', 'Pengembalian', 'return'],
        ['log_aktivitas', 'Log Aktivitas', 'activity'],
    ],
    'petugas' => [
        ['dashboard', 'Dashboard', 'grid'],
        ['peminjaman', 'Data Peminjaman', 'clipboard'],
        ['setujui', 'Persetujuan', 'check'],
        ['pengembalian', 'Pengembalian', 'return'],
        ['cetak', 'Cetak Laporan', 'printer'],
    ],
    'peminjam' => [
        ['dashboard', 'Beranda', 'home'],
        ['daftar_alat', 'Katalog Alat', 'grid'],
        ['pinjam', 'Ajukan Pinjam', 'plus'],
        ['riwayat', 'Riwayat', 'clock'],
    ],
];
$menu = $menus[$user['level']];

$jumlah_menunggu = 0;
if ($user['level'] !== 'peminjam') {
    $jumlah_menunggu = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 0');
}

function logo_svg($kelas = 'h-9 w-9')
{
    return '<span class="logo-kilau grid place-items-center rounded-xl bg-gradient-to-br from-accent-300 to-accent-600 text-white shadow-glow ' . $kelas . '">'
        . '<svg viewBox="0 0 24 24" class="h-[62%] w-[62%]" fill="currentColor"><path d="M12 2c.8 5.4 3.6 8.2 10 10-6.4 1.8-9.2 4.6-10 10-.8-5.4-3.6-8.2-10-10 6.4-1.8 9.2-4.6 10-10z"/></svg></span>';
}

$tanggal_hari_ini = tgl(date('Y-m-d'));
$link_menunggu = $user['level'] === 'admin' ? 'admin/peminjaman.php?filter=menunggu' : 'petugas/setujui.php';
?>
<header class="header-kaca no-print sticky top-0 z-40 border-b border-accent-100">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 sm:px-6 lg:px-8">
        <a href="<?= url($user['level'] . '/dashboard.php') ?>" class="flex shrink-0 items-center gap-2">
            <?= logo_svg() ?>
            <span class="leading-none">
                <span class="block text-lg font-black text-zinc-900">Pinjamin</span>
                <span class="mt-0.5 hidden text-[10px] font-bold text-accent-500 sm:block">ピンジャミン</span>
            </span>
        </a>

        <nav class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto scroll-thin lg:flex" aria-label="Menu utama">
            <?php foreach ($menu as $m): $on = $menu_aktif === $m[0]; ?>
                <a href="<?= url($user['level'] . '/' . $m[0] . '.php') ?>" class="tab <?= $on ? 'tab-on' : '' ?>" <?= $on ? 'aria-current="page"' : '' ?>>
                    <?= e($m[1]) ?>
                    <?php if ($jumlah_menunggu > 0 && ($m[0] === 'setujui' || ($user['level'] === 'admin' && $m[0] === 'peminjaman'))): ?>
                        <span class="rounded-full bg-gradient-to-br from-accent-400 to-accent-600 px-1.5 text-[11px] font-bold leading-5 text-white"><?= $jumlah_menunggu ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-3 lg:ml-0">
            <div class="hidden items-center gap-2.5 sm:flex">
                <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-accent-200 to-accent-400 text-xs font-black text-accent-900 ring-2 ring-white"><?= e(inisial($user['nama'])) ?></span>
                <div class="leading-tight">
                    <p class="text-sm font-medium text-zinc-900"><?= e($user['nama']) ?></p>
                    <p class="text-xs capitalize text-zinc-500"><?= e($user['level']) ?></p>
                </div>
            </div>
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-ghost btn-sm hidden sm:inline-flex"><?= icon('logout', 'w-4 h-4') ?> Keluar</a>
            <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-accent-200 bg-white/80 text-accent-700 lg:hidden" data-toggle="#menu-mobile" aria-label="Buka menu"><?= icon('menu', 'w-5 h-5') ?></button>
        </div>
    </div>

    <div id="menu-mobile" class="hidden border-t border-accent-100 bg-white/95 px-4 pb-4 pt-2 lg:hidden">
        <?php foreach ($menu as $m): $on = $menu_aktif === $m[0]; ?>
            <a href="<?= url($user['level'] . '/' . $m[0] . '.php') ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold <?= $on ? 'bg-accent-100 text-accent-700' : 'text-zinc-600' ?>">
                <?= icon($m[2], 'w-4 h-4') ?>
                <span class="flex-1"><?= e($m[1]) ?></span>
                <?php if ($jumlah_menunggu > 0 && ($m[0] === 'setujui' || ($user['level'] === 'admin' && $m[0] === 'peminjaman'))): ?>
                    <span class="rounded-full bg-gradient-to-br from-accent-400 to-accent-600 px-1.5 text-[11px] font-bold leading-5 text-white"><?= $jumlah_menunggu ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        <div class="mt-2 flex items-center justify-between border-t border-zinc-200 pt-3">
            <span class="text-sm font-bold text-zinc-700"><?= e($user['nama']) ?></span>
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-ghost btn-sm"><?= icon('logout', 'w-4 h-4') ?> Keluar</a>
        </div>
    </div>
    <div class="seigaiha pointer-events-none absolute inset-x-0 -bottom-3" aria-hidden="true"></div>
</header>

<main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8" id="isi">
