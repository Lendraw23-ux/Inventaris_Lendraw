<?php
$level_boleh = ['petugas'];
require_once '../includes/auth.php';

$dari   = isset($_GET['dari']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$sampai = isset($_GET['sampai']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'semua';

$kondisi_sql = [
    'semua'        => '',
    'menunggu'     => ' AND p.persetujuan = 0',
    'dipinjam'     => ' AND p.persetujuan = 1 AND p.status = 1',
    'dikembalikan' => ' AND p.persetujuan = 1 AND p.status = 2',
    'ditolak'      => ' AND p.persetujuan = 2',
];
if (!isset($kondisi_sql[$status])) {
    $status = 'semua';
}

$daftar = db_all(
    $koneksi,
    'SELECT p.*, u.nama AS peminjam, a.nama_alat, k.nama_kategori, g.tanggal_kembali AS tgl_dikembalikan
     FROM peminjaman p
     JOIN user u ON u.id_user = p.id_user
     JOIN alat a ON a.id_alat = p.id_alat
     JOIN kategori k ON k.id_kategori = a.id_kategori
     LEFT JOIN pengembalian g ON g.id_peminjaman = p.id_peminjaman
     WHERE p.tanggal_pinjam BETWEEN ? AND ?' . $kondisi_sql[$status] . '
     ORDER BY p.tanggal_pinjam ASC, p.id_peminjaman ASC',
    [$dari, $sampai]
);

$rekap = ['Menunggu persetujuan' => 0, 'Sedang dipinjam' => 0, 'Terlambat' => 0, 'Dikembalikan' => 0, 'Ditolak' => 0];
foreach ($daftar as $r) {
    $s = status_info($r);
    $rekap[$s[0]]++;
}

$judul = 'Cetak Laporan';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<style media="print">
    header, footer, .no-print { display: none !important; }
    body, main { background: #fff !important; padding: 0 !important; max-width: none !important; }
    .laporan { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
    @page { margin: 16mm; }
</style>

<div class="no-print ">
    <h2 class="text-2xl font-semibold text-zinc-900">Cetak laporan</h2>
    <p class="mt-1 text-sm text-zinc-500">Pilih periode dan status, lalu cetak atau simpan sebagai PDF.</p>
</div>

<form method="get" class="no-print card mt-6 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
    <div><label class="label" for="dari">Dari tanggal</label><input class="input" type="date" id="dari" name="dari" value="<?= e($dari) ?>"></div>
    <div><label class="label" for="sampai">Sampai tanggal</label><input class="input" type="date" id="sampai" name="sampai" value="<?= e($sampai) ?>"></div>
    <div>
        <label class="label" for="status">Status</label>
        <select class="input" id="status" name="status">
            <?php foreach (['semua' => 'Semua status', 'menunggu' => 'Menunggu', 'dipinjam' => 'Sedang dipinjam', 'dikembalikan' => 'Dikembalikan', 'ditolak' => 'Ditolak'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button class="btn btn-primary"><?= icon('search', 'w-4 h-4') ?> Tampilkan</button>
        <button type="button" class="btn btn-primary" onclick="window.print()"><?= icon('printer', 'w-4 h-4') ?> Cetak</button>
    </div>
</form>

<article class="laporan card mt-6 p-6 sm:p-10">
    <header class="border-b-2 border-zinc-900 pb-5 text-center">
        <h3 class="text-2xl font-semibold text-zinc-900">Laporan Peminjaman Alat</h3>
        <p class="mt-1 text-sm text-zinc-600">Periode <?= tgl($dari) ?> sampai <?= tgl($sampai) ?><?= $status !== 'semua' ? ' · Status: ' . e(ucfirst($status)) : '' ?></p>
    </header>

    <div class="mt-5 grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
        <?php foreach ($rekap as $label => $n): ?>
            <div class="rounded-xl bg-zinc-50 px-3 py-2.5 print:border print:border-zinc-200">
                <p class="text-xs text-zinc-500"><?= e($label) ?></p>
                <p class="text-xl font-semibold text-zinc-900"><?= $n ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>No</th><th>Peminjam</th><th>Alat</th><th>Kategori</th><th>Tgl pinjam</th><th>Batas kembali</th><th>Dikembalikan</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $i => $r): $s = status_info($r); ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="font-semibold text-zinc-900"><?= e($r['peminjam']) ?></td>
                    <td><?= e($r['nama_alat']) ?></td>
                    <td><?= e($r['nama_kategori']) ?></td>
                    <td class="whitespace-nowrap"><?= tgl($r['tanggal_pinjam']) ?></td>
                    <td class="whitespace-nowrap"><?= tgl($r['tanggal_kembali']) ?></td>
                    <td class="whitespace-nowrap"><?= $r['tgl_dikembalikan'] ? tgl($r['tgl_dikembalikan']) : '-' ?></td>
                    <td class="whitespace-nowrap"><?= e($s[0]) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$daftar): ?><tr><td colspan="8" class="py-12 text-center text-zinc-500">Tidak ada data pada periode ini.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-10 flex justify-end">
        <div class="w-56 text-center text-sm text-zinc-700">
            <p>Dicetak <?= tgl(date('Y-m-d')) ?></p>
            <p class="mt-1">Petugas,</p>
            <div class="h-20"></div>
            <p class="border-t border-zinc-400 pt-1 font-semibold text-zinc-900"><?= e($user['nama']) ?></p>
        </div>
    </div>
</article>

<?php require_once '../includes/footer.php'; ?>
