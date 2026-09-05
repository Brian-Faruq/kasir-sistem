<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

// Ambil data header transaksi
$query_trans = "SELECT t.*, u.nama AS kasir 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.id = $id";
$res_trans = mysqli_query($koneksi, $query_trans);
$trans = mysqli_fetch_assoc($res_trans);

if (!$trans) {
    echo "Transaksi tidak ditemukan!";
    exit;
}

// Ambil rincian barang yang dibeli
$query_detail = "SELECT td.*, p.nama_barang 
                 FROM transaction_details td 
                 JOIN products p ON td.product_id = p.id 
                 WHERE td.transaction_id = $id";
$res_detail = mysqli_query($koneksi, $query_detail);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran - <?= $trans['no_nota'] ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 280px; /* Ukuran standar kertas thermal 58mm */
            font-size: 12px;
            margin: 0 auto;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="text-center">
        <h3 style="margin: 0;">POS UMKM</h3>
        <p style="margin: 2px 0;">Jl. Raya Toko No. 123</p>
        <p style="margin: 2px 0;">Telp: 0812-3456-7890</p>
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td>Nota: <?= $trans['no_nota'] ?></td>
        </tr>
        <tr>
            <td>Tgl : <?= date('d/m/Y H:i', strtotime($trans['created_at'])) ?></td>
        </tr>
        <tr>
            <td>Ksri: <?= $trans['kasir'] ?></td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <?php while ($d = mysqli_fetch_assoc($res_detail)): ?>
            <tr>
                <td colspan="2"><strong><?= $d['nama_barang'] ?></strong></td>
            </tr>
            <tr>
                <td><?= $d['qty'] ?> x Rp <?= number_format($d['harga_jual'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td>Total</td>
            <td class="text-right"><strong>Rp <?= number_format($trans['total_harga'], 0, ',', '.') ?></strong></td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="text-right">Rp <?= number_format($trans['bayar'], 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="text-right">Rp <?= number_format($trans['kembalian'], 0, ',', '.') ?></td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="text-center">
        <p style="margin: 5px 0;">-- Terima Kasih --</p>
        <p style="margin: 0;">Barang yang sudah dibeli<br>tidak dapat ditukar</p>
    </div>

    <br>
    <div class="text-center no-print">
        <button onclick="window.print()">Cetak Ulang</button>
        <a href="kasir.php"><button type="button">Kembali ke Kasir</button></a>
    </div>

</body>
</html>