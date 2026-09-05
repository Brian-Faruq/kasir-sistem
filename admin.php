<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman: Hanya Role Owner yang Boleh Masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit;
}

// 1. PROSES TAMBAH PRODUK BARU
if (isset($_POST['tambah_produk'])) {
    $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $category_id = intval($_POST['category_id']);
    $harga_beli  = floatval($_POST['harga_beli']);
    $harga_jual  = floatval($_POST['harga_jual']);
    $stok        = intval($_POST['stok']);

    $query_insert = "INSERT INTO products (category_id, kode_barang, nama_barang, harga_beli, harga_jual, stok) 
                     VALUES ($category_id, '$kode_barang', '$nama_barang', $harga_beli, $harga_jual, $stok)";
    
    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Produk berhasil ditambahkan!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah produk: Kode Barang mungkin sudah ada!');</script>";
    }
}

// 2. PROSES EDIT PRODUK
if (isset($_POST['edit_produk'])) {
    $id          = intval($_POST['id']);
    $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $category_id = intval($_POST['category_id']);
    $harga_beli  = floatval($_POST['harga_beli']);
    $harga_jual  = floatval($_POST['harga_jual']);
    $stok        = intval($_POST['stok']);

    $query_update = "UPDATE products SET 
                        category_id = $category_id,
                        kode_barang = '$kode_barang',
                        nama_barang = '$nama_barang',
                        harga_beli = $harga_beli,
                        harga_jual = $harga_jual,
                        stok = $stok 
                     WHERE id = $id";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Data produk berhasil diperbarui!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui produk!');</script>";
    }
}

// 3. PROSES HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM products WHERE id = $id_hapus";
    if (mysqli_query($koneksi, $query_delete)) {
        echo "<script>alert('Produk berhasil dihapus!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus produk!');</script>";
    }
}

// HITUNG RINGKASAN DASHBOARD
$tgl_hari_ini = date('Y-m-d');
$query_omzet = mysqli_query($koneksi, "SELECT SUM(total_harga) AS omzet FROM transactions WHERE DATE(created_at) = '$tgl_hari_ini'");
$data_omzet = mysqli_fetch_assoc($query_omzet);
$omzet_hari_ini = $data_omzet['omzet'] ?? 0;

$query_total_produk = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM products");
$total_produk = mysqli_fetch_assoc($query_total_produk)['total'];

// Ambil Data Produk & Kategori
$categories_query = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$products = mysqli_query($koneksi, "SELECT p.*, c.nama_kategori FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - POS UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">POS UMKM - Admin Panel</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Halo, <strong><?= $_SESSION['nama'] ?></strong> (Owner)</span>
            <a href="laporan.php" class="btn btn-outline-warning btn-sm me-2">Laporan Keuangan</a>
            <a href="kasir.php" class="btn btn-outline-info btn-sm me-2">Ke Kasir</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <!-- METRIK RINGKASAN -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Omzet Hari Ini</h6>
                    <h3 class="fw-bold mb-0">Rp <?= number_format($omzet_hari_ini, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Total Jenis Barang</h6>
                    <h3 class="fw-bold mb-0"><?= $total_produk ?> Item</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- FORM TAMBAH PRODUK -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Tambah Produk Baru</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-2">
                            <label class="form-label small">Kode Barang / Barcode</label>
                            <input type="text" name="kode_barang" class="form-control" placeholder="cth: BRG004" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" placeholder="Nama Produk" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                $cat_res = mysqli_query($koneksi, $categories_query);
                                while ($c = mysqli_fetch_assoc($cat_res)): 
                                ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Harga Beli (HPP)</label>
                            <input type="number" name="harga_beli" class="form-control" placeholder="0" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Harga Jual</label>
                            <input type="number" name="harga_jual" class="form-control" placeholder="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Stok Awal</label>
                            <input type="number" name="stok" class="form-control" value="10" required>
                        </div>
                        <button type="submit" name="tambah_produk" class="btn btn-primary w-100 fw-bold">Simpan Produk</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL INVENTARIS PRODUK -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Daftar Stok Produk</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($products)): ?>
                                <tr>
                                    <td><code><?= $row['kode_barang'] ?></code></td>
                                    <td><?= $row['nama_barang'] ?></td>
                                    <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($row['stok'] <= 3): ?>
                                            <span class="badge bg-danger"><?= $row['stok'] ?> (Menipis)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $row['stok'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm fw-bold me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">Edit</button>
                                        <a href="admin.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus barang ini?')">Hapus</a>
                                    </td>
                                </tr>

                                <!-- MODAL EDIT PRODUK -->
                                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Produk</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <div class="mb-2">
                                                        <label class="form-label small">Kode Barang / Barcode</label>
                                                        <input type="text" name="kode_barang" class="form-control" value="<?= $row['kode_barang'] ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Nama Barang</label>
                                                        <input type="text" name="nama_barang" class="form-control" value="<?= $row['nama_barang'] ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Kategori</label>
                                                        <select name="category_id" class="form-select" required>
                                                            <?php 
                                                            $cat_res_modal = mysqli_query($koneksi, $categories_query);
                                                            while ($cm = mysqli_fetch_assoc($cat_res_modal)): 
                                                            ?>
                                                                <option value="<?= $cm['id'] ?>" <?= $cm['id'] == $row['category_id'] ? 'selected' : '' ?>>
                                                                    <?= $cm['nama_kategori'] ?>
                                                                </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Harga Beli (HPP)</label>
                                                        <input type="number" name="harga_beli" class="form-control" value="<?= $row['harga_beli'] ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Harga Jual</label>
                                                        <input type="number" name="harga_jual" class="form-control" value="<?= $row['harga_jual'] ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Stok</label>
                                                        <input type="number" name="stok" class="form-control" value="<?= $row['stok'] ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit_produk" class="btn btn-primary fw-bold">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- END MODAL EDIT -->

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Bootstrap untuk Modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>