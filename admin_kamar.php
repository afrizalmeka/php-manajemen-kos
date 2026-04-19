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
        $nomor     = trim($_POST['nomor'] ?? '');
        $tipe      = trim($_POST['tipe'] ?? '');
        $harga     = $_POST['harga_bulan'] ?? '';
        $fasilitas = trim($_POST['fasilitas'] ?? '');

        // BUG 4: Tidak ada validasi input — field kosong langsung dimasukkan ke DB
        try {
            $pdo->prepare("INSERT INTO kamar (nomor, tipe, harga_bulan, fasilitas) VALUES (?, ?, ?, ?)")
                ->execute([$nomor, $tipe, (float)$harga, $fasilitas]);
            $msg = 'Kamar berhasil ditambahkan.';
        } catch (Exception $e) {
            $error = 'Nomor kamar sudah ada.';
        }

    } elseif ($act === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $nomor = trim($_POST['nomor'] ?? '');
        $tipe  = trim($_POST['tipe'] ?? '');
        $harga = $_POST['harga_bulan'] ?? '';
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $status = $_POST['status'] ?? 'kosong';

        $pdo->prepare("UPDATE kamar SET nomor=?, tipe=?, harga_bulan=?, fasilitas=?, status=? WHERE id=?")
            ->execute([$nomor, $tipe, (float)$harga, $fasilitas, $status, $id]);
        $msg = 'Kamar berhasil diperbarui.';

    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // BUG 5: Tidak mengecek apakah kamar masih memiliki penghuni aktif sebelum hapus
        $pdo->prepare("DELETE FROM kamar WHERE id = ?")->execute([$id]);
        $msg = 'Kamar berhasil dihapus.';
    }
}

$kamarList = $pdo->query("SELECT * FROM kamar ORDER BY nomor")->fetchAll();
$editKamar = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kamar WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editKamar = $stmt->fetch();
}

$pageTitle = 'Kelola Kamar — KosKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>Kelola Kamar</h1></div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header"><?= $editKamar ? 'Edit Kamar' : 'Tambah Kamar' ?></div>
        <div class="card-body">
            <form method="post" style="display:grid;grid-template-columns:repeat(5,1fr) auto;gap:.75rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editKamar ? 'edit' : 'add' ?>">
                <?php if ($editKamar): ?><input type="hidden" name="id" value="<?= $editKamar['id'] ?>"><?php endif; ?>
                <div class="form-group" style="margin:0;"><label>Nomor</label><input type="text" name="nomor" value="<?= htmlspecialchars($editKamar['nomor'] ?? '') ?>" required></div>
                <div class="form-group" style="margin:0;"><label>Tipe</label>
                    <select name="tipe">
                        <?php foreach (['Standard','Premium','VIP'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($editKamar['tipe'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label>Harga/Bulan</label><input type="number" name="harga_bulan" value="<?= $editKamar['harga_bulan'] ?? '' ?>" min="1" required></div>
                <div class="form-group" style="margin:0;"><label>Fasilitas</label><input type="text" name="fasilitas" value="<?= htmlspecialchars($editKamar['fasilitas'] ?? '') ?>"></div>
                <?php if ($editKamar): ?>
                <div class="form-group" style="margin:0;"><label>Status</label>
                    <select name="status">
                        <option value="kosong" <?= $editKamar['status'] === 'kosong' ? 'selected' : '' ?>>Kosong</option>
                        <option value="terisi" <?= $editKamar['status'] === 'terisi' ? 'selected' : '' ?>>Terisi</option>
                    </select>
                </div>
                <?php else: ?><div></div><?php endif; ?>
                <button type="submit" class="btn btn-<?= $editKamar ? 'primary' : 'success' ?>"><?= $editKamar ? 'Update' : 'Tambah' ?></button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Kamar</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Nomor</th><th>Tipe</th><th>Harga/Bulan</th><th>Fasilitas</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($kamarList as $k): ?>
                <tr>
                    <td><?= htmlspecialchars($k['nomor']) ?></td>
                    <td><?= htmlspecialchars($k['tipe']) ?></td>
                    <td>Rp <?= number_format($k['harga_bulan'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($k['fasilitas'] ?? '-') ?></td>
                    <td><span class="badge <?= $k['status'] === 'terisi' ? 'badge-danger' : 'badge-success' ?>"><?= $k['status'] === 'terisi' ? 'Terisi' : 'Kosong' ?></span></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="admin_kamar.php?edit=<?= $k['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus kamar ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $k['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
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
