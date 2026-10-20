<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

// ---- Aksi: setujui / tolak / terima pengembalian ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id   = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);

    if ($aksi === 'setujui' || $aksi === 'tolak') {
        $hasil = proses_persetujuan($koneksi, $id, $aksi, $user['nama']);
    } elseif ($aksi === 'kembali') {
        $hasil = proses_pengembalian($koneksi, $id, $user['nama']);
    } else {
        $hasil = [false, 'Aksi tidak dikenal.'];
    }
    flash_set($hasil[0] ? 'success' : 'error', $hasil[1]);
    redirect('admin/peminjaman.php' . (!empty($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
}

$filters = [
    'semua'        => ['Semua', ''],
    'menunggu'     => ['Menunggu', 'p.persetujuan = 0'],
    'dipinjam'     => ['Dipinjam', 'p.persetujuan = 1 AND p.status = 1 AND p.tanggal_kembali >= CURDATE()'],
    'terlambat'    => ['Terlambat', 'p.persetujuan = 1 AND p.status = 1 AND p.tanggal_kembali < CURDATE()'],
    'dikembalikan' => ['Dikembalikan', 'p.persetujuan = 1 AND p.status = 2'],
    'ditolak'      => ['Ditolak', 'p.persetujuan = 2'],
];
$filter = isset($_GET['filter']) && isset($filters[$_GET['filter']]) ? $_GET['filter'] : 'semua';

$sql = 'SELECT p.*, u.nama AS peminjam, a.nama_alat, a.id_kategori, k.nama_kategori
        FROM peminjaman p
        JOIN user u ON u.id_user = p.id_user
        JOIN alat a ON a.id_alat = p.id_alat
        JOIN kategori k ON k.id_kategori = a.id_kategori';
if ($filters[$filter][1] !== '') {
    $sql .= ' WHERE ' . $filters[$filter][1];
}
$sql .= ' ORDER BY p.id_peminjaman DESC';
$daftar = db_all($koneksi, $sql);

// jumlah tiap filter untuk chip
$jumlah = [];
foreach ($filters as $kode => $f) {
    $jumlah[$kode] = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman p' . ($f[1] !== '' ? ' WHERE ' . $f[1] : ''));
}

$judul = 'Peminjaman';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="">
    <h2 class="text-2xl font-semibold text-zinc-900">Semua peminjaman</h2>
    <p class="mt-1 text-sm text-zinc-500">Pantau pengajuan, setujui, dan catat pengembalian alat.</p>
</div>

<div class="mt-6 flex gap-2 overflow-x-auto pb-1 scroll-thin">
    <?php foreach ($filters as $kode => $f): ?>
        <a href="<?= url('admin/peminjaman.php?filter=' . $kode) ?>" class="chip <?= $filter === $kode ? 'chip-on' : '' ?>">
            <?= e($f[0]) ?> <span class="text-xs opacity-70"><?= $jumlah[$kode] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card mt-4 overflow-hidden">
    <div class="border-b border-zinc-100 p-4">
        <div class="relative max-w-sm">
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="search" class="input pl-10" placeholder="Cari peminjam atau alat" data-filter="#tbl-pinjam tbody tr" data-empty="#kosong-pinjam" aria-label="Cari peminjaman">
        </div>
    </div>
    <div class="overflow-x-auto scroll-thin">
        <table class="tbl" id="tbl-pinjam">
            <thead><tr><th>Peminjam</th><th>Alat</th><th>Periode</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $r): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-700"><?= e(inisial($r['peminjam'])) ?></span>
                            <span class="font-semibold text-zinc-900"><?= e($r['peminjam']) ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($r['nama_kategori']), 'w-4 h-4') ?></span>
                            <?= e($r['nama_alat']) ?>
                        </div>
                    </td>
                    <td class="whitespace-nowrap text-zinc-600"><?= tgl($r['tanggal_pinjam']) ?> <span class="text-zinc-300">→</span> <?= tgl($r['tanggal_kembali']) ?></td>
                    <td><?= badge_status($r) ?></td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <?php if ((int)$r['persetujuan'] === 0): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                                    <button class="btn btn-primary btn-sm"><?= icon('check', 'w-4 h-4') ?> Setujui</button></form>
                                <form method="post" data-confirm="Pengajuan <?= e($r['nama_alat']) ?> oleh <?= e($r['peminjam']) ?> akan ditolak." data-confirm-title="Tolak pengajuan?"><?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                                    <button class="btn btn-danger-soft btn-sm">Tolak</button></form>
                            <?php elseif ((int)$r['persetujuan'] === 1 && (int)$r['status'] === 1): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="kembali"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                                    <button class="btn btn-soft btn-sm"><?= icon('return', 'w-4 h-4') ?> Terima kembali</button></form>
                            <?php else: ?>
                                <span class="text-xs text-zinc-400">Selesai</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="kosong-pinjam" class="<?= $daftar ? 'hidden ' : '' ?>py-14 text-center text-sm text-zinc-500">Tidak ada peminjaman pada filter ini.</div>
</div>

<?php require_once '../includes/footer.php'; ?>
