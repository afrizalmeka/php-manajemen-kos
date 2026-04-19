<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireAdmin();

$pdo = getDB();
$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $hunianId = (int)($_POST['hunian_id'] ?? 0);
        $bulan    = trim($_POST['bulan'] ?? '');
        $jumlah   = $_POST['jumlah'] ?? '';

        if ($hunianId === 0 || $bulan === '' || $jumlah === '') {
            $error = 'Semua field wajib diisi.';
        } else {
            // BUG 8: Tidak ada pengecekan duplikat tagihan — tagihan bulan yang sama
            // bisa dibuat berkali-kali untuk penghuni yang sama
            $pdo->prepare("INSERT INTO pembayaran (hunian_id, bulan, jumlah) VALUES (?, ?, ?)")
                ->execute([$hunianId, $bulan, (float)$jumlah]);
            $msg = 'Tagihan berhasil ditambahkan.';
        }

    } elseif ($act === 'bayar') {
        $id = (int)($_POST['id'] ?? 0);
        $tglBayar = date('Y-m-d');
        $pdo->prepare("UPDATE pembayaran SET status = 'lunas', tanggal_bayar = ? WHERE id = ?")
            ->execute([$tglBayar, $id]);
        // BUG: Flash message tidak diset sehingga tidak ada konfirmasi visual sukses
        header('Location: admin_pembayaran.php');
        exit;
    }
}

$pembayaranList = $pdo->query("SELECT p.*, h.tanggal_masuk, k.nomor AS kamar_nomor, u.name AS penyewa_name
    FROM pembayaran p JOIN hunian h ON p.hunian_id = h.id JOIN kamar k ON h.kamar_id = k.id JOIN users u ON h.user_id = u.id
    ORDER BY p.status, p.bulan DESC")->fetchAll();

$hunianAktif = $pdo->query("SELECT h.id, k.nomor, u.name, k.harga_bulan FROM hunian h JOIN kamar k ON h.kamar_id = k.id JOIN users u ON h.user_id = u.id WHERE h.status = 'aktif' ORDER BY k.nomor")->fetchAll();

$pageTitle = 'Kelola Pembayaran — KosKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Pembayaran</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">Buat Tagihan Baru</div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end;">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin:0;"><label>Penghuni</label>
                    <select name="hunian_id" required>
                        <option value="">-- Pilih Penghuni --</option>
                        <?php foreach ($hunianAktif as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nomor'] . ' — ' . $h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label>Bulan (YYYY-MM)</label><input type="month" name="bulan" required></div>
                <div class="form-group" style="margin:0;"><label>Jumlah (Rp)</label><input type="number" name="jumlah" min="1" required></div>
                <button type="submit" class="btn btn-success">Buat Tagihan</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Pembayaran</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Kamar</th><th>Penyewa</th><th>Bulan</th><th>Jumlah</th><th>Status</th><th>Tgl Bayar</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($pembayaranList as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['kamar_nomor']) ?></td>
                    <td><?= htmlspecialchars($p['penyewa_name']) ?></td>
                    <td><?= htmlspecialchars($p['bulan']) ?></td>
                    <td>Rp <?= number_format($p['jumlah'],0,',','.') ?></td>
                    <td><span class="badge <?= $p['status'] === 'lunas' ? 'badge-success' : 'badge-warning' ?>"><?= $p['status'] === 'lunas' ? 'Lunas' : 'Belum Bayar' ?></span></td>
                    <td><?= $p['tanggal_bayar'] ?? '-' ?></td>
                    <td>
                        <?php if ($p['status'] !== 'lunas'): ?>
                        <form method="post" onsubmit="return confirm('Konfirmasi pembayaran?')">
                            <input type="hidden" name="action" value="bayar">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">Tandai Lunas</button>
                        </form>
                        <?php else: ?>✓<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
