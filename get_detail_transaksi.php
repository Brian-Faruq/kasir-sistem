<?php
session_start();
require_once 'koneksi.php';

// Proteksi: Harus Login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Ambil Header Transaksi (Pastikan hanya transaksi milik kasir yang login)
$q_trans = "SELECT t.*, u.nama AS nama_kasir 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.id = $transaction_id AND t.user_id = $user_id";
$res_trans = mysqli_query($koneksi, $q_trans);
$trans = mysqli_fetch_assoc($res_trans);

if (!$trans) {
    http_response_code(444);
    echo json_encode(['error' => 'Data transaksi tidak ditemukan atau bukan milik Anda.']);
    exit;
}

// Ambil Rincian Barang
$q_detail = "SELECT td.*, p.nama_barang 
             FROM transaction_details td 
             LEFT JOIN products p ON td.product_id = p.id 
             WHERE td.transaction_id = $transaction_id";
$res_detail = mysqli_query($koneksi, $q_detail);

$items = [];
while ($row = mysqli_fetch_assoc($res_detail)) {
    $items[] = [
        'nama_barang' => $row['nama_barang'] ?? 'Produk Dihapus',
        'harga_jual'  => number_format($row['harga_jual'], 0, ',', '.'),
        'qty'         => $row['qty'],
        'subtotal'    => number_format($row['subtotal'], 0, ',', '.')
    ];
}

$response = [
    'no_nota'      => $trans['no_nota'],
    'created_at'   => date('d-m-Y H:i:s', strtotime($trans['created_at'])),
    'metode_bayar' => strtoupper($trans['metode_bayar']),
    'total_harga'  => number_format($trans['total_harga'], 0, ',', '.'),
    'bayar'        => number_format($trans['bayar'], 0, ',', '.'),
    'kembalian'    => number_format($trans['kembalian'], 0, ',', '.'),
    'items'        => $items
];

header('Content-Type: application/json');
echo json_encode($response);