<?php
$level_boleh = ['peminjam'];
require_once '../includes/auth.php';

$maks_hari = 14;
$hari_ini = date('Y-m-d');

$semua_alat = db_all($koneksi, "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
                                WHERE a.stok > 0 AND a.kondisi NOT LIKE '%berat%' ORDER BY k.nama_kategori, a.nama_alat");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id_alat = (int)(isset($_POST['id_alat']) ? $_POST['id_alat'] : 0);
    $pinjam  = isset($_POST['tanggal_pinjam']) ? $_POST['tanggal_pinjam'] : '';
    $kembali = isset($_POST['tanggal_kembali']) ? $_POST['tanggal_kembali'] : '';
    $format_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $pinjam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $kembali);

    $alat = db_row($koneksi, 'SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori WHERE a.id_alat = ?', [$id_alat]);

    if (!$alat) {
        flash_set('error', 'Pilih alat yang ingin dipinjam.');
    } elseif (!alat_bisa_dipinjam($alat)) {
        flash_set('error', $alat['nama_alat'] . ' sedang tidak bisa dipinjam.');
    } elseif (!$format_ok) {
        flash_set('error', 'Tanggal pinjam dan tanggal kembali wajib diisi.');
    } elseif ($pinjam < $hari_ini) {
        flash_set('error', 'Tanggal pinjam tidak boleh sebelum hari ini.');
    } elseif ($kembali < $pinjam) {
        flash_set('error', 'Tanggal kembali tidak boleh sebelum tanggal pinjam.');
    } elseif (hari_selisih($pinjam, $kembali) > $maks_hari) {
        flash_set('error', 'Lama peminjaman maksimal ' . $maks_hari . ' hari.');
    } else {
        $sama = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE id_user = ? AND id_alat = ? AND ((persetujuan = 0) OR (persetujuan = 1 AND status = 1))', [$user['id'], $id_alat]);
        if ($sama > 0) {
            flash_set('error', 'Kamu masih punya pengajuan atau pinjaman aktif untuk ' . $alat['nama_alat'] . '.');
        } else {
            db_exec($koneksi, 'INSERT INTO peminjaman (id_user, id_alat, tanggal_pinjam, tanggal_kembali, status, persetujuan) VALUES (?, ?, ?, ?, 0, 0)', [$user['id'], $id_alat, $pinjam, $kembali]);
            catat_log($koneksi, $user['nama'] . ' mengajukan peminjaman ' . $alat['nama_alat']);
            flash_set('success', 'Pengajuan ' . $alat['nama_alat'] . ' terkirim. Tunggu persetujuan petugas.');
            redirect('peminjam/riwayat.php');
        }
    }
    redirect('peminjam/pinjam.php?id=' . $id_alat);
}

$id_pilih = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$terpilih = null;
foreach ($semua_alat as $a) {
    if ((int)$a['id_alat'] === $id_pilih) {
        $terpilih = $a;
    }
}

$judul = 'Ajukan Peminjaman';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$data_alat = [];
foreach ($semua_alat as $a) {
    $data_alat[$a['id_alat']] = ['nama' => $a['nama_alat'], 'kategori' => $a['nama_kategori'], 'stok' => (int)$a['stok'], 'kondisi' => $a['kondisi'], 'ikon' => kategori_ikon($a['nama_kategori'])];
}
?>

<div>
    <a href="<?= url('peminjam/daftar_alat.php') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-zinc-500 hover:text-zinc-900"><span class="rotate-180"><?= icon('arrow', 'w-4 h-4') ?></span> Kembali ke katalog</a>
    <h1 class="mt-3 text-2xl font-semibold text-zinc-900">Ajukan peminjaman</h1>

    <?php if (!$semua_alat): ?>
        <div class="card mt-8 py-16 text-center text-sm text-zinc-500">Belum ada alat yang tersedia untuk dipinjam.</div>
    <?php else: ?>
    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1.1fr]">
        <!-- Pratinjau alat -->
        <aside class="order-2 lg:order-1">
            <div class="card overflow-hidden lg:sticky lg:top-20">
                <div id="pv-gambar" class="grid h-36 place-items-center bg-zinc-100 text-zinc-500">
                    <span id="pv-ikon">
                        <?= icon($terpilih ? kategori_ikon($terpilih['nama_kategori']) : 'box', 'w-12 h-12') ?>
                    </span>
                </div>
                <div class="p-6">
                    <p id="pv-kategori" class="text-xs text-zinc-500"><?= $terpilih ? e($terpilih['nama_kategori']) : 'Belum memilih alat' ?></p>
                    <h2 id="pv-nama" class="mt-1 text-2xl font-semibold text-zinc-900"><?= $terpilih ? e($terpilih['nama_alat']) : 'Pilih alat di formulir' ?></h2>
                    <p id="pv-info" class="mt-2 text-sm text-zinc-500"><?= $terpilih ? 'Stok tersedia ' . (int)$terpilih['stok'] . ' unit · Kondisi ' . e($terpilih['kondisi']) : 'Detail alat akan tampil di sini.' ?></p>
                    <ul class="mt-5 space-y-2.5 text-sm text-zinc-600">
                        <li class="flex items-center gap-2.5"><?= icon('clock', 'w-4 h-4 text-accent-600') ?> Maksimal <?= $maks_hari ?> hari per peminjaman</li>
                        <li class="flex items-center gap-2.5"><?= icon('shield', 'w-4 h-4 text-accent-600') ?> Stok baru berkurang setelah disetujui</li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Formulir -->
        <form method="post" class="card order-1 space-y-5 p-6 lg:order-2">
            <?= csrf_field() ?>
            <div>
                <label class="label" for="id_alat">Alat</label>
                <select class="input" id="id_alat" name="id_alat" required>
                    <option value="">Pilih alat...</option>
                    <?php $kat_sebelum = ''; foreach ($semua_alat as $a):
                        if ($a['nama_kategori'] !== $kat_sebelum) { if ($kat_sebelum !== '') echo '</optgroup>'; echo '<optgroup label="' . e($a['nama_kategori']) . '">'; $kat_sebelum = $a['nama_kategori']; } ?>
                        <option value="<?= (int)$a['id_alat'] ?>" <?= $terpilih && (int)$terpilih['id_alat'] === (int)$a['id_alat'] ? 'selected' : '' ?>><?= e($a['nama_alat']) ?> (stok <?= (int)$a['stok'] ?>)</option>
                    <?php endforeach; if ($kat_sebelum !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="tanggal_pinjam">Tanggal pinjam</label>
                    <input class="input" type="date" id="tanggal_pinjam" name="tanggal_pinjam" min="<?= $hari_ini ?>" value="<?= $hari_ini ?>" required>
                </div>
                <div>
                    <label class="label" for="tanggal_kembali">Tanggal kembali</label>
                    <input class="input" type="date" id="tanggal_kembali" name="tanggal_kembali" min="<?= $hari_ini ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                </div>
            </div>
            <div id="lama" class="flex items-center gap-3 rounded-xl bg-accent-50 px-4 py-3 text-sm font-medium text-accent-800">
                <?= icon('calendar', 'w-5 h-5') ?> <span id="lama-teks">Lama peminjaman 3 hari</span>
            </div>
            <button class="btn btn-primary w-full py-2.5">Kirim pengajuan</button>
            <p class="text-center text-xs text-zinc-500">Pengajuan akan diperiksa petugas. Kamu bisa memantau statusnya di Riwayat.</p>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var data = <?= json_encode((object)$data_alat) ?>;
    var ikon = <?= json_encode([
        'monitor' => icon('monitor', 'w-12 h-12'), 'camera' => icon('camera', 'w-12 h-12'), 'tool' => icon('tool', 'w-12 h-12'),
        'flask' => icon('flask', 'w-12 h-12'), 'ball' => icon('ball', 'w-12 h-12'), 'box' => icon('box', 'w-12 h-12'),
    ]) ?>;
    var sel = document.getElementById('id_alat');
    var a = document.getElementById('tanggal_pinjam'), b = document.getElementById('tanggal_kembali');
    var maks = <?= (int)$maks_hari ?>;
    if (!sel) return;

    function pratinjau() {
        var d = data[sel.value];
        document.getElementById('pv-nama').textContent = d ? d.nama : 'Pilih alat di formulir';
        document.getElementById('pv-kategori').textContent = d ? d.kategori : 'Belum memilih alat';
        document.getElementById('pv-info').textContent = d ? 'Stok tersedia ' + d.stok + ' unit · Kondisi ' + d.kondisi : 'Detail alat akan tampil di sini.';
        document.getElementById('pv-ikon').innerHTML = ikon[d ? d.ikon : 'box'];
    }
    function hitung() {
        if (!a.value || !b.value) return;
        b.min = a.value;
        var n = Math.round((new Date(b.value) - new Date(a.value)) / 86400000);
        var t = document.getElementById('lama-teks'), box = document.getElementById('lama');
        var salah = n < 0 || n > maks;
        t.textContent = n < 0 ? 'Tanggal kembali sebelum tanggal pinjam' : (n > maks ? 'Melebihi batas ' + maks + ' hari (' + n + ' hari)' : 'Lama peminjaman ' + (n === 0 ? '1 hari (hari yang sama)' : n + ' hari'));
        box.className = 'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium ' + (salah ? 'bg-red-50 text-red-700' : 'bg-accent-50 text-accent-800');
    }
    sel.addEventListener('change', pratinjau);
    a.addEventListener('change', hitung); b.addEventListener('change', hitung);
    hitung();
})();
</script>

<?php require_once '../includes/footer.php'; ?>
