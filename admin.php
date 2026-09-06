<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman: Hanya Role Owner yang Boleh Masuk
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit;
}

// ==================== 1. PROSES PRODUK ====================

// A. TAMBAH PRODUK
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

// B. EDIT PRODUK
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

// C. HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM products WHERE id = $id_hapus";
    if (mysqli_query($koneksi, $query_delete)) {
        echo "<script>alert('Produk berhasil dihapus!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus produk!');</script>";
    }
}


// ==================== 2. PROSES USER / KASIR ====================

// A. TAMBAH USER / KASIR
if (isset($_POST['tambah_user'])) {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    $q_add_user = "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$password', '$role')";
    if (mysqli_query($koneksi, $q_add_user)) {
        echo "<script>alert('User/Kasir baru berhasil ditambahkan!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah user: Username mungkin sudah digunakan!');</script>";
    }
}

// B. EDIT USER / KASIR
if (isset($_POST['edit_user'])) {
    $id_user  = intval($_POST['id_user']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $password_md5 = md5($password);
        $q_edit_user = "UPDATE users SET nama='$nama', username='$username', password='$password_md5', role='$role' WHERE id=$id_user";
    } else {
        $q_edit_user = "UPDATE users SET nama='$nama', username='$username', role='$role' WHERE id=$id_user";
    }

    if (mysqli_query($koneksi, $q_edit_user)) {
        echo "<script>alert('Data user berhasil diperbarui!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui user!');</script>";
    }
}

// C. HAPUS USER / KASIR
if (isset($_GET['hapus_user'])) {
    $id_hapus_user = intval($_GET['hapus_user']);
    
    // Cegah Owner menghapus dirinya sendiri
    if ($id_hapus_user === $_SESSION['user_id']) {
        echo "<script>alert('Anda tidak bisa menghapus akun Anda sendiri yang sedang login!'); window.location='admin.php';</script>";
    } else {
        $q_del_user = "DELETE FROM users WHERE id = $id_hapus_user";
        if (mysqli_query($koneksi, $q_del_user)) {
            echo "<script>alert('User berhasil dihapus!'); window.location='admin.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus user!');</script>";
        }
    }
}


// ==================== 3. QUERY DATA ====================

// METRIK RINGKASAN DASHBOARD
$tgl_hari_ini = date('Y-m-d');
$query_omzet = mysqli_query($koneksi, "SELECT SUM(total_harga) AS omzet FROM transactions WHERE DATE(created_at) = '$tgl_hari_ini'");
$data_omzet = mysqli_fetch_assoc($query_omzet);
$omzet_hari_ini = $data_omzet['omzet'] ?? 0;

$query_total_produk = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM products");
$total_produk = mysqli_fetch_assoc($query_total_produk)['total'];

// FILTER STOK MENIPIS (stok <= 3)
$query_stok_menipis = mysqli_query($koneksi, "SELECT COUNT(*) AS total_menipis FROM products WHERE stok <= 3");
$stok_menipis_count = mysqli_fetch_assoc($query_stok_menipis)['total_menipis'];

// CEK STATUS FILTER TABEL PRODUK
$filter_stok = $_GET['filter'] ?? 'all';
$sql_products = "SELECT p.*, c.nama_kategori FROM products p LEFT JOIN categories c ON p.category_id = c.id";

if ($filter_stok === 'menipis') {
    $sql_products .= " WHERE p.stok <= 3";
}
$sql_products .= " ORDER BY p.id DESC";

$categories_query = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$products = mysqli_query($koneksi, $sql_products);

// FETCH ALL USERS
$users_query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - POS UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light pb-5">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="admin.php">POS UMKM - Admin Panel</a>
        <div class="d-flex text-white align-items-center">
            <span class="me-3">Halo, <strong><?= $_SESSION['nama'] ?></strong> (Owner)</span>
            <a href="laporan.php" class="btn btn-outline-warning btn-sm me-2">Laporan Keuangan</a>
            <a href="kasir.php" class="btn btn-outline-info btn-sm me-2">Ke Kasir</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- PERINGATAN / ALERT BADGE STOK MENIPIS -->
    <?php if ($stok_menipis_count > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex justify-content-between align-items-center shadow-sm" role="alert">
            <div>
                <strong>Perhatian!</strong> Terdapat <strong><?= $stok_menipis_count ?> produk</strong> yang stoknya menipis (&le; 3 item). Segera lakukan restok!
            </div>
            <a href="admin.php?filter=menipis" class="btn btn-warning btn-sm text-dark fw-bold">Lihat Barang Menipis &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- METRIK RINGKASAN -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Omzet Hari Ini</h6>
                    <h3 class="fw-bold mb-0">Rp <?= number_format($omzet_hari_ini, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Total Jenis Barang</h6>
                    <h3 class="fw-bold mb-0"><?= $total_produk ?> Item</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $stok_menipis_count > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Stok Menipis (&le; 3)</h6>
                    <h3 class="fw-bold mb-0"><?= $stok_menipis_count ?> Item</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ================= KOLOM KIRI (FORM PRODUK & USER) ================= -->
        <div class="col-md-4">
            
            <!-- 1. FORM TAMBAH PRODUK -->
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

            <!-- 2. FORM TAMBAH USER / KASIR (DITARUH DI BAWAH FORM TAMBAH PRODUK) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold text-success">Tambah User / Kasir Baru</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-2">
                            <label class="form-label small">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="cth: Kasir 2" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="cth: kasir2" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Role / Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner / Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah_user" class="btn btn-success w-100 fw-bold">Tambah User Baru</button>
                    </form>
                </div>
            </div>

        </div>

        <!-- ================= KOLOM KANAN (TABEL PRODUK & USER) ================= -->
        <div class="col-md-8">
            
            <!-- 1. TABEL INVENTARIS PRODUK -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">
                        Daftar Stok Produk 
                        <?= $filter_stok === 'menipis' ? '<span class="badge bg-warning text-dark">(Filter: Menipis)</span>' : '' ?>
                    </span>
                    <div>
                        <a href="admin.php" class="btn btn-sm <?= $filter_stok === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>">Semua</a>
                        <a href="admin.php?filter=menipis" class="btn btn-sm <?= $filter_stok === 'menipis' ? 'btn-danger' : 'btn-outline-danger' ?>">
                            Menipis (<?= $stok_menipis_count ?>)
                        </a>
                    </div>
                </div>
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
                            <?php if (mysqli_num_rows($products) > 0): ?>
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
                                    <!-- END MODAL EDIT PRODUK -->

                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Tidak ada produk yang memenuhi kriteria filter.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. TABEL KELOLA USER / KASIR (DITARUH DI BAWAH TABEL PRODUK) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold text-success">
                    Daftar Pengguna / Kasir
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($users_query) > 0): ?>
                                <?php while ($usr = mysqli_fetch_assoc($users_query)): ?>
                                    <tr>
                                        <td><code>#<?= $usr['id'] ?></code></td>
                                        <td class="fw-bold"><?= $usr['nama'] ?></td>
                                        <td><?= $usr['username'] ?></td>
                                        <td>
                                            <?php if ($usr['role'] === 'owner'): ?>
                                                <span class="badge bg-primary">Owner</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark">Kasir</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-warning btn-sm fw-bold me-1" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $usr['id'] ?>">Edit</button>
                                            <?php if ($usr['id'] != $_SESSION['user_id']): ?>
                                                <a href="admin.php?hapus_user=<?= $usr['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>Saya</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- MODAL EDIT USER -->
                                    <div class="modal fade" id="modalEditUser<?= $usr['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="" method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit User / Kasir</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_user" value="<?= $usr['id'] ?>">
                                                        <div class="mb-2">
                                                            <label class="form-label small">Nama Lengkap</label>
                                                            <input type="text" name="nama" class="form-control" value="<?= $usr['nama'] ?>" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small">Username</label>
                                                            <input type="text" name="username" class="form-control" value="<?= $usr['username'] ?>" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small">Password Baru <span class="text-muted">(Kosongkan jika tidak ingin ganti)</span></label>
                                                            <input type="password" name="password" class="form-control" placeholder="Password Baru">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small">Role / Hak Akses</label>
                                                            <select name="role" class="form-select" required>
                                                                <option value="kasir" <?= $usr['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                                                <option value="owner" <?= $usr['role'] === 'owner' ? 'selected' : '' ?>>Owner / Admin</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="edit_user" class="btn btn-success fw-bold">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END MODAL EDIT USER -->

                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>