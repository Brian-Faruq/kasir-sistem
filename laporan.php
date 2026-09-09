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
    <title>Laporan Keuangan - POS Sekolah Impian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800">

    <!-- NAVBAR HEADER -->
    <header class="bg-slate-900 text-white shadow-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-500 p-2 rounded-lg text-white font-bold">
                    <i class="fa-solid fa-chart-line text-lg"></i>
                </div>
                <h1 class="text-xl font-bold tracking-wide">POS SEKOLAH IMPIAN <span class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded uppercase font-semibold ml-1">Laporan</span></h1>
            </div>
            <div class="flex items-center space-x-3">
                <a href="admin.php" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium px-4 py-2 rounded-lg border border-slate-700 transition">
                    <i class="fa-solid fa-box mr-1.5"></i> Dashboard Produk
                </a>
                <a href="logout.php" class="bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- FILTER TANGGAL CARD -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" value="<?= $tgl_selesai ?>" class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-filter"></i>
                        <span>Filter Laporan</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Omzet -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 bg-blue-50 text-blue-200 rounded-full p-8">
                    <i class="fa-solid fa-wallet text-6xl"></i>
                </div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Total Omzet</p>
                <h3 class="text-3xl font-extrabold text-slate-900 mb-1">Rp <?= number_format($omzet, 0, ',', '.') ?></h3>
                <p class="text-xs text-slate-500 font-medium"><i class="fa-solid fa-receipt mr-1"></i> <?= $total_transaksi ?> Transaksi</p>
            </div>

            <!-- Total HPP / Modal -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 bg-slate-100 text-slate-200 rounded-full p-8">
                    <i class="fa-solid fa-boxes-packing text-6xl"></i>
                </div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Modal (HPP)</p>
                <h3 class="text-3xl font-extrabold text-slate-700 mb-1">Rp <?= number_format($hpp, 0, ',', '.') ?></h3>
                <p class="text-xs text-slate-500 font-medium">Modal Barang Terjual</p>
            </div>

            <!-- Laba Bersih -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 <?= $laba_bersih >= 0 ? 'bg-emerald-50 text-emerald-200' : 'bg-rose-50 text-rose-200' ?> rounded-full p-8">
                    <i class="fa-solid <?= $laba_bersih >= 0 ? 'fa-sack-dollar' : 'fa-hand-holding-dollar' ?> text-6xl"></i>
                </div>
                <p class="text-xs font-bold <?= $laba_bersih >= 0 ? 'text-emerald-600' : 'text-rose-600' ?> uppercase tracking-wider mb-1">Laba Bersih</p>
                <h3 class="text-3xl font-extrabold <?= $laba_bersih >= 0 ? 'text-emerald-600' : 'text-rose-600' ?> mb-1">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h3>
                <p class="text-xs <?= $laba_bersih >= 0 ? 'text-emerald-600' : 'text-rose-600' ?> font-medium">
                    <i class="fa-solid <?= $laba_bersih >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?> mr-1"></i> 
                    <?= $laba_bersih >= 0 ? 'Keuntungan Bersih Toko' : 'Kerugian Toko' ?>
                </p>
            </div>
        </div>

        <!-- RIWAYAT TRANSAKSI TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-orange-500"></i>
                    Riwayat Transaksi Penjualan
                </h2>
            </div>
            
            <div class="overflow-x-auto max-h-[450px] overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-600 relative border-collapse">
                    <thead class="bg-slate-100 uppercase font-semibold text-slate-700 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-5 py-3.5 bg-slate-100">No Nota</th>
                            <th class="px-5 py-3.5 bg-slate-100">Tanggal & Waktu</th>
                            <th class="px-5 py-3.5 bg-slate-100">Kasir</th>
                            <th class="px-5 py-3.5 bg-slate-100 text-center">Metode Bayar</th>
                            <th class="px-5 py-3.5 bg-slate-100 text-right">Total Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (mysqli_num_rows($list_transaksi) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($list_transaksi)): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-4 font-bold text-orange-600 hover:underline">
                                        <a href="cetak_nota.php?id=<?= $row['id'] ?>" target="_blank" title="Klik untuk lihat / cetak nota">
                                            <?= $row['no_nota'] ?>
                                        </a>
                                    </td>
                                    <td class="px-5 py-4 text-slate-500"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td class="px-5 py-4 text-slate-800"><?= $row['kasir'] ?></td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="bg-teal-100 text-teal-700 font-bold px-3 py-1 rounded-full text-[10px] tracking-wider uppercase">
                                            <?= strtoupper($row['metode_bayar']) ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-bold text-slate-900 text-sm">
                                        Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-400 font-normal">
                                    <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                                    Tidak ada transaksi pada periode ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>