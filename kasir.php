<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Inisialisasi keranjang belanja di session jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. TAMBAH BARANG KE KERANJANG
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['qty']);

    $query = mysqli_query($koneksi, "SELECT * FROM products WHERE id = $product_id");
    $product = mysqli_fetch_assoc($query);

    if ($product) {
        if ($qty > $product['stok']) {
            echo "<script>alert('Stok tidak mencukupi!');</script>";
        } else {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'nama'       => $product['nama_barang'],
                    'harga_jual' => $product['harga_jual'],
                    'harga_beli' => $product['harga_beli'],
                    'qty'        => $qty
                ];
            }
        }
    }
}

// 2. HAPUS ITEM DARI KERANJANG
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    unset($_SESSION['cart'][$id_hapus]);
    header("Location: kasir.php");
    exit;
}

// 3. PROSES TRANSAKSI / BAYAR
if (isset($_POST['proses_transaksi'])) {
    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Keranjang masih kosong!');</script>";
    } else {
        $total_harga  = floatval($_POST['total_harga']);
        $bayar        = floatval($_POST['bayar']);
        $kembalian    = $bayar - $total_harga;
        $user_id      = $_SESSION['user_id'];
        $no_nota      = "INV-" . date("YmdHis");

        if ($bayar < $total_harga) {
            echo "<script>alert('Uang pembayaran kurang!');</script>";
        } else {
            // A. Simpan ke tabel transactions
            $query_trans = "INSERT INTO transactions (no_nota, user_id, total_harga, bayar, kembalian) 
                            VALUES ('$no_nota', $user_id, $total_harga, $bayar, $kembalian)";
            mysqli_query($koneksi, $query_trans);
            $transaction_id = mysqli_insert_id($koneksi);

            // B. Simpan ke transaction_details & Potong Stok
            foreach ($_SESSION['cart'] as $p_id => $item) {
                $h_beli   = $item['harga_beli'];
                $h_jual   = $item['harga_jual'];
                $p_qty    = $item['qty'];
                $subtotal = $h_jual * $p_qty;

                // Detail transaksi
                mysqli_query($koneksi, "INSERT INTO transaction_details (transaction_id, product_id, harga_beli, harga_jual, qty, subtotal) 
                                        VALUES ($transaction_id, $p_id, $h_beli, $h_jual, $p_qty, $subtotal)");

                // Potong stok produk
                mysqli_query($koneksi, "UPDATE products SET stok = stok - $p_qty WHERE id = $p_id");
            }

            // C. Bersihkan keranjang
            $_SESSION['cart'] = [];
            echo "<script>alert('Transaksi Berhasil! No Nota: $no_nota'); window.location='kasir.php';</script>";
        }
    }
}

// Ambil semua daftar produk untuk dipillih
$products = mysqli_query($koneksi, "SELECT * FROM products WHERE stok > 0 ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - POS UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">POS UMKM</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Halo, <strong><?= $_SESSION['nama'] ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <!-- KOLOM KIRI: PILIH PRODUK -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Pilih Barang</div>
                <div class="card-body">
                    <form action="" method="POST" class="row g-2">
                        <div class="col-8">
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= $p['nama_barang'] ?> (Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?>) | Stok: <?= $p['stok'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-2">
                            <input type="number" name="qty" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-2">
                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100">+</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: KERANJANG & PEMBAYARAN -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Keranjang Belanja</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_belanja = 0;
                            if (!empty($_SESSION['cart'])): 
                                foreach ($_SESSION['cart'] as $id => $item): 
                                    $subtotal = $item['harga_jual'] * $item['qty'];
                                    $total_belanja += $subtotal;
                            ?>
                                <tr>
                                    <td><?= $item['nama'] ?></td>
                                    <td>Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td><a href="kasir.php?hapus=<?= $id ?>" class="btn btn-danger btn-sm">x</a></td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Keranjang kosong</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white p-3">
                    <form action="" method="POST">
                        <input type="hidden" name="total_harga" value="<?= $total_belanja ?>">
                        <div class="row align-items-center mb-3">
                            <div class="col-6">
                                <h5>Total: <span class="text-primary fw-bold">Rp <?= number_format($total_belanja, 0, ',', '.') ?></span></h5>
                            </div>
                            <div class="col-6">
                                <input type="number" name="bayar" class="form-control form-control-lg" placeholder="Nominal Uang Bayar" required>
                            </div>
                        </div>
                        <button type="submit" name="proses_transaksi" class="btn btn-success w-100 fw-bold py-2">PROSES TRANSAKSI</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>