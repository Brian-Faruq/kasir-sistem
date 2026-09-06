<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman: Hanya Role Owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit;
}

// Filter Tanggal (Default: Hari Ini)
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-d');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// 1. HITUNG TOTAL OMZET & HPP DARI TRANSAKSI
$query_penjualan = "SELECT 
                        SUM(td.subtotal) AS total_omzet,
                        SUM(td.harga_beli * td.qty) AS total_hpp,
                        COUNT(DISTINCT t.id) AS total_transaksi
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    WHERE DATE(t.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
$res_penjualan = mysqli_query($koneksi, $query_penjualan);
$data_p = mysqli_fetch_assoc($res_penjualan);

$omzet           = $data_p['total_omzet'] ?? 0;
$hpp             = $data_p['total_hpp'] ?? 0;
$total_transaksi = $data_p['total_transaksi'] ?? 0;

// 2. HITUNG LABA BERSIH (OMZET - HPP)
$laba_bersih = $omzet - $hpp;

// 3. AMBIL RINCIAN TRANSAKSI UNTUK TABEL
$query_detail_trans = "SELECT t.*, u.nama AS kasir 
                       FROM transactions t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE DATE(t.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                       ORDER BY t.id DESC";
$list_transaksi = mysqli_query($koneksi, $query_detail_trans);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - POS UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="admin.php">POS UMKM - Admin Panel</a>
        <div class="d-flex text-white align-items-center">
            <a href="admin.php" class="btn btn-outline-light btn-sm me-2">Dashboard Produk</a>
            <a href="kasir.php" class="btn btn-outline-info btn-sm me-2">Ke Kasir</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <!-- FILTER TANGGAL -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary fw-bold w-100">Filter Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- METRIK KEUANGAN (LABA/RUGI) -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Total Omzet</h6>
                    <h4 class="fw-bold mb-0">Rp <?= number_format($omzet, 0, ',', '.') ?></h4>
                    <small><?= $total_transaksi ?> Transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Total Modal (HPP)</h6>
                    <h4 class="fw-bold mb-0">Rp <?= number_format($hpp, 0, ',', '.') ?></h4>
                    <small>Modal Barang Terjual</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $laba_bersih >= 0 ? 'bg-success' : 'bg-danger' ?> text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Laba Bersih</h6>
                    <h4 class="fw-bold mb-0">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h4>
                    <small>Keuntungan Bersih Toko</small>
                </div>
            </div>
        </div>
    </div>

    <!-- RIWAYAT TRANSAKSI -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Riwayat Transaksi Penjualan</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No Nota</th>
                        <th>Tanggal & Waktu</th>
                        <th>Kasir</th>
                        <th>Metode Bayar</th>
                        <th>Total Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($list_transaksi) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($list_transaksi)): ?>
                            <tr>
                                <td><code><?= $row['no_nota'] ?></code></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= $row['kasir'] ?></td>
                                <td><span class="badge bg-info text-dark"><?= strtoupper($row['metode_bayar']) ?></span></td>
                                <td class="fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Tidak ada transaksi pada periode ini</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>