<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman Kasir
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Inisialisasi Keranjang Belanja jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. TAMBAH BARANG KE KERANJANG
if (isset($_POST['tambah_keranjang'])) {
    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['qty']);

    if ($product_id > 0 && $qty > 0) {
        $q_prod = mysqli_query($koneksi, "SELECT * FROM products WHERE id = $product_id");
        $prod   = mysqli_fetch_assoc($q_prod);

        if ($prod) {
            // Cek persediaan stok
            if ($qty > $prod['stok']) {
                echo "<script>alert('Stok tidak mencukupi! Stok tersisa: {$prod['stok']}');</script>";
            } else {
                // Jika produk sudah ada di keranjang, update qty
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['qty'] += $qty;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'nama_barang' => $prod['nama_barang'],
                        'harga_jual'  => $prod['harga_jual'],
                        'harga_beli'  => $prod['harga_beli'],
                        'qty'         => $qty
                    ];
                }
            }
        }
    }
}

// 2. HAPUS BARANG DARI KERANJANG
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    unset($_SESSION['cart'][$id_hapus]);
    header("Location: kasir.php");
    exit;
}

// 3. PROSES TRANSAKSI
if (isset($_POST['proses_transaksi'])) {
    $bayar = floatval($_POST['bayar']);
    $user_id = $_SESSION['user_id'];
    $metode_bayar = 'cash';

    // Hitung total belanja
    $total_harga = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += ($item['harga_jual'] * $item['qty']);
    }

    if ($total_harga <= 0) {
        echo "<script>alert('Keranjang masih kosong!');</script>";
    } elseif ($bayar < $total_harga) {
        echo "<script>alert('Uang pembayaran kurang!');</script>";
    } else {
        $kembalian = $bayar - $total_harga;
        $no_nota   = 'INV-' . date('YmdHis');

        // Insert Header Transaksi
        $q_trans = "INSERT INTO transactions (no_nota, user_id, total_harga, bayar, kembalian, metode_bayar) 
                    VALUES ('$no_nota', $user_id, $total_harga, $bayar, $kembalian, '$metode_bayar')";
        
        if (mysqli_query($koneksi, $q_trans)) {
            $transaction_id = mysqli_insert_id($koneksi);

            // Insert Detail Transaksi & Potong Stok
            foreach ($_SESSION['cart'] as $p_id => $item) {
                $subtotal = $item['harga_jual'] * $item['qty'];
                $h_beli   = $item['harga_beli'];
                $h_jual   = $item['harga_jual'];
                $qty_item = $item['qty'];

                mysqli_query($koneksi, "INSERT INTO transaction_details (transaction_id, product_id, qty, harga_beli, harga_jual, subtotal) 
                                        VALUES ($transaction_id, $p_id, $qty_item, $h_beli, $h_jual, $subtotal)");

                // Deduct Stock
                mysqli_query($koneksi, "UPDATE products SET stok = stok - $qty_item WHERE id = $p_id");
            }

            // Reset Keranjang & Arahkan ke Struk
            $_SESSION['cart'] = [];
            header("Location: cetak_nota.php?id=" . $transaction_id);
            exit;
        }
    }
}

// Ambil Seluruh Daftar Produk untuk Dropdown
$products_list = mysqli_query($koneksi, "SELECT * FROM products WHERE stok > 0 ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - POS UMKM</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Select2 CSS & Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">POS UMKM</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Halo, <strong><?= $_SESSION['nama'] ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">Dashboard Owner</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline-danger btn-sm text-white">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <!-- FORM PILIH BARANG (SELECT2) -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Pilih Barang</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small">Nama / Kode Barang</label>
                            <select name="product_id" id="select-produk" class="form-select" required>
                                <option value="">-- Cari Produk --</option>
                                <?php while ($p = mysqli_fetch_assoc($products_list)): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= $p['kode_barang'] ?> - <?= $p['nama_barang'] ?> (Stok: <?= $p['stok'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Jumlah (Qty)</label>
                            <input type="number" name="qty" class="form-control" value="1" min="1" required>
                        </div>
                        <button type="submit" name="tambah_keranjang" class="btn btn-primary w-100 fw-bold">+</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- KERANJANG BELANJA & PEMBAYARAN -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Keranjang Belanja</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
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
                            $grand_total = 0;
                            if (!empty($_SESSION['cart'])): 
                                foreach ($_SESSION['cart'] as $id_p => $item):
                                    $subtotal = $item['harga_jual'] * $item['qty'];
                                    $grand_total += $subtotal;
                            ?>
                                <tr>
                                    <td><?= $item['nama_barang'] ?></td>
                                    <td>Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td>
                                        <a href="kasir.php?hapus=<?= $id_p ?>" class="btn btn-danger btn-sm">Hapus</a>
                                    </td>
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
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-0 fw-bold text-primary">Total: Rp <?= number_format($grand_total, 0, ',', '.') ?></h4>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group mb-2">
                                    <input type="number" name="bayar" class="form-control form-control-lg" placeholder="Nominal Uang Bayar" required>
                                </div>
                                <button type="submit" name="proses_transaksi" class="btn btn-success btn-lg w-100 fw-bold" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                                    PROSES TRANSAKSI
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inisialisasi Select2 pada dropdown produk dengan tema Bootstrap 5
        $('#select-produk').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Cari Produk --',
            allowClear: true
        });
    });
</script>

</body>
</html>