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
        $kamarId     = (int)($_POST['kamar_id'] ?? 0);
        $userId      = (int)($_POST['user_id'] ?? 0);
        $tanggalMasuk = trim($_POST['tanggal_masuk'] ?? '');

        if ($kamarId === 0 || $userId === 0 || $tanggalMasuk === '') {
            $error = 'Semua field wajib diisi.';
        } else {
            // Bisa menyebabkan double-booking
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO hunian (kamar_id, user_id, tanggal_masuk) VALUES (?, ?, ?)")
                ->execute([$kamarId, $userId, $tanggalMasuk]);
            $pdo->prepare("UPDATE kamar SET status = 'terisi' WHERE id = ?")->execute([$kamarId]);
            $pdo->commit();
            $msg = 'Hunian berhasil ditambahkan.';
        }

    } elseif ($act === 'checkout') {
        $id           = (int)($_POST['id'] ?? 0);
        $tanggalKeluar = trim($_POST['tanggal_keluar'] ?? '');
        if ($id === 0) {
            $error = 'ID hunian tidak valid.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM hunian WHERE id = ?");
            $stmt->execute([$id]);
            $hunian = $stmt->fetch();
            if ($hunian) {
                $pdo->beginTransaction();
                // awal dari tanggal masuk
                $pdo->prepare("UPDATE hunian SET status = 'selesai', tanggal_keluar = ? WHERE id = ?")
                    ->execute([$tanggalKeluar ?: null, $id]);
                $pdo->prepare("UPDATE kamar SET status = 'kosong' WHERE id = ?")->execute([$hunian['kamar_id']]);
                $pdo->commit();
                $msg = 'Penyewa berhasil di-checkout.';
            }
        }
    }
}

$hunianList = $pdo->query("SELECT h.*, k.nomor AS kamar_nomor, k.tipe, k.harga_bulan, u.name AS penyewa_name, u.phone
    FROM hunian h JOIN kamar k ON h.kamar_id = k.id JOIN users u ON h.user_id = u.id ORDER BY h.status, h.tanggal_masuk DESC")->fetchAll();

$kamarKosong = $pdo->query("SELECT * FROM kamar ORDER BY nomor")->fetchAll();
$penyewaList = $pdo->query("SELECT * FROM users WHERE role = 'penyewa' ORDER BY name")->fetchAll();

$pageTitle = 'Kelola Hunian — KosKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Hunian</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">Tambah Hunian Baru</div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end;">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin:0;"><label>Kamar Kosong</label>
                    <select name="kamar_id" required>
                        <option value="">-- Pilih Kamar --</option>
                        <?php foreach ($kamarKosong as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nomor']) ?> — <?= $k['tipe'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label>Penyewa</label>
                    <select name="user_id" required>
                        <option value="">-- Pilih Penyewa --</option>
                        <?php foreach ($penyewaList as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label>Tanggal Masuk</label><input type="date" name="tanggal_masuk" required></div>
                <button type="submit" class="btn btn-success">Tambah</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Hunian</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Kamar</th><th>Penyewa</th><th>No. HP</th><th>Masuk</th><th>Keluar</th><th>Harga/Bulan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($hunianList as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['kamar_nomor']) ?> (<?= $h['tipe'] ?>)</td>
                    <td><?= htmlspecialchars($h['penyewa_name']) ?></td>
                    <td><?= htmlspecialchars($h['phone'] ?? '-') ?></td>
                    <td><?= $h['tanggal_masuk'] ?></td>
                    <td><?= $h['tanggal_keluar'] ?? '-' ?></td>
                    <td>Rp <?= number_format($h['harga_bulan'],0,',','.') ?></td>
                    <td><span class="badge <?= $h['status'] === 'aktif' ? 'badge-success' : 'badge-secondary' ?>"><?= $h['status'] === 'aktif' ? 'Aktif' : 'Selesai' ?></span></td>
                    <td>
                        <?php if ($h['status'] === 'aktif'): ?>
                        <form method="post" style="display:flex;gap:.3rem;align-items:center;" onsubmit="return confirm('Proses checkout?')">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                            <input type="date" name="tanggal_keluar" style="padding:.3rem;border:1px solid #ddd;border-radius:4px;">
                            <button type="submit" class="btn btn-warning btn-sm">Checkout</button>
                        </form>
                        <?php else: ?>—<?php endif; ?>
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
