<?php
function initDatabase(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'penyewa',
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS kamar (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nomor TEXT UNIQUE NOT NULL,
        tipe TEXT NOT NULL,
        harga_bulan REAL NOT NULL,
        fasilitas TEXT,
        status TEXT NOT NULL DEFAULT 'kosong',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS hunian (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kamar_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        tanggal_masuk DATE NOT NULL,
        tanggal_keluar DATE,
        status TEXT NOT NULL DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kamar_id) REFERENCES kamar(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pembayaran (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hunian_id INTEGER NOT NULL,
        bulan TEXT NOT NULL,
        jumlah REAL NOT NULL,
        status TEXT NOT NULL DEFAULT 'belum_bayar',
        tanggal_bayar DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (hunian_id) REFERENCES hunian(id)
    )");

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Admin KosKu', 'admin@kosku.com', '$adminPass', 'admin')");

        $userPass = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role, phone) VALUES ('Andi Wijaya', 'andi@mail.com', '$userPass', 'penyewa', '081234567890')");
        $pdo->exec("INSERT INTO users (name, email, password, role, phone) VALUES ('Sari Dewi', 'sari@mail.com', '$userPass', 'penyewa', '082345678901')");

        // Seed kamar
        $kamar = [
            ['A1', 'Standard', 800000, 'AC, WiFi, Kamar Mandi Dalam', 'terisi'],
            ['A2', 'Standard', 800000, 'AC, WiFi, Kamar Mandi Dalam', 'kosong'],
            ['A3', 'Standard', 800000, 'AC, WiFi, Kamar Mandi Dalam', 'kosong'],
            ['B1', 'Premium', 1200000, 'AC, WiFi, KM Dalam, TV, Kulkas', 'terisi'],
            ['B2', 'Premium', 1200000, 'AC, WiFi, KM Dalam, TV, Kulkas', 'kosong'],
            ['C1', 'VIP', 1800000, 'AC, WiFi, KM Dalam, TV, Kulkas, Dapur Mini', 'kosong'],
        ];
        $stmt = $pdo->prepare("INSERT INTO kamar (nomor, tipe, harga_bulan, fasilitas, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($kamar as $k) $stmt->execute($k);

        // Hunian aktif untuk kamar A1 dan B1
        $pdo->exec("INSERT INTO hunian (kamar_id, user_id, tanggal_masuk, status) VALUES (1, 2, '2025-01-01', 'aktif')");
        $pdo->exec("INSERT INTO hunian (kamar_id, user_id, tanggal_masuk, status) VALUES (4, 3, '2025-02-01', 'aktif')");

        // Seed pembayaran
        $pdo->exec("INSERT INTO pembayaran (hunian_id, bulan, jumlah, status, tanggal_bayar) VALUES (1, '2025-01', 800000, 'lunas', '2025-01-05')");
        $pdo->exec("INSERT INTO pembayaran (hunian_id, bulan, jumlah, status, tanggal_bayar) VALUES (1, '2025-02', 800000, 'lunas', '2025-02-03')");
        $pdo->exec("INSERT INTO pembayaran (hunian_id, bulan, jumlah, status) VALUES (1, '2025-03', 800000, 'belum_bayar')");
    }
}
