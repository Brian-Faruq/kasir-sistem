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

$tgl_hari_ini = date('Y-m-d');
$query_omzet = mysqli_query($koneksi, "SELECT SUM(total_harga) AS omzet FROM transactions WHERE DATE(created_at) = '$tgl_hari_ini'");
$data_omzet = mysqli_fetch_assoc($query_omzet);
$omzet_hari_ini = $data_omzet['omzet'] ?? 0;

$query_total_produk = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM products");
$total_produk = mysqli_fetch_assoc($query_total_produk)['total'];

$query_stok_menipis = mysqli_query($koneksi, "SELECT COUNT(*) AS total_menipis FROM products WHERE stok <= 3");
$stok_menipis_count = mysqli_fetch_assoc($query_stok_menipis)['total_menipis'];

$filter_stok = $_GET['filter'] ?? 'all';
$sql_products = "SELECT p.*, c.nama_kategori FROM products p LEFT JOIN categories c ON p.category_id = c.id";

if ($filter_stok === 'menipis') {
    $sql_products .= " WHERE p.stok <= 3";
}
$sql_products .= " ORDER BY p.stok ASC";

$categories_query = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$products = mysqli_query($koneksi, $sql_products);

// Simpan data produk ke array untuk diproses pada tabel & modal terpisah
$products_list = [];
if (mysqli_num_rows($products) > 0) {
    while ($p = mysqli_fetch_assoc($products)) {
        $products_list[] = $p;
    }
}

$users_query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");

// Simpan data user ke array untuk diproses pada tabel & modal terpisah
$users_list = [];
if (mysqli_num_rows($users_query) > 0) {
    while ($u = mysqli_fetch_assoc($users_query)) {
        $users_list[] = $u;
    }
}

$q_count_user = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'kasir' THEN 1 ELSE 0 END) as total_kasir,
    SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) as total_owner
FROM users");
$count_user = mysqli_fetch_assoc($q_count_user);

$total_semua = $count_user['total'] ?? 0;
$total_kasir = $count_user['total_kasir'] ?? 0;
$total_owner = $count_user['total_owner'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - POS SEKOLAH IMPIAN</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        impian: {
                            orange: '#E85D04',
                            amber: '#F48C06',
                            navy: '#1A365D',
                            teal: '#0D9488',
                            darkteal: '#0F766E'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans min-h-screen pb-12">

    <!-- NAVBAR -->
    <nav class="bg-impian-navy text-white shadow-lg border-b border-slate-700 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Title -->
                <div class="flex items-center gap-3">
                    <span class="text-xl">📊</span>
                    <span class="font-bold text-base sm:text-lg tracking-wide">POS SEKOLAH IMPIAN <span class="text-xs bg-impian-orange text-white px-2 py-0.5 rounded-md uppercase font-semibold">Admin Panel</span></span>
                </div>

                <!-- User & Action Buttons -->
                <div class="flex items-center gap-2 sm:gap-3 text-xs sm:text-sm">
                    <span class="hidden md:inline text-slate-300">Halo, <strong class="text-white"><?= $_SESSION['nama'] ?></strong> (Owner)</span>
                    
                    <a href="laporan.php" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 px-3 py-1.5 rounded-lg transition font-medium flex items-center gap-1">
                        📈 <span class="hidden sm:inline">Laporan</span>
                    </a>
                    <a href="kasir.php" class="bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 border border-teal-500/30 px-3 py-1.5 rounded-lg transition font-medium flex items-center gap-1">
                        🛒 <span class="hidden sm:inline">Ke Kasir</span>
                    </a>
                    <a href="logout.php" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/30 px-3 py-1.5 rounded-lg transition font-medium">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTAINER -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">

        <!-- METRIK DASHBOARD -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Omzet Hari Ini -->
            <div class="bg-gradient-to-r from-impian-navy to-slate-800 rounded-xl p-5 text-white shadow-md border border-slate-700">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-slate-300 text-xs font-medium uppercase tracking-wider">Omzet Hari Ini</p>
                        <h3 class="text-2xl font-black mt-1">Rp <?= number_format($omzet_hari_ini, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-white/10 p-3 rounded-xl text-xl">💰</div>
                </div>
            </div>

            <!-- Total Produk -->
            <div class="bg-gradient-to-r from-impian-teal to-impian-darkteal rounded-xl p-5 text-white shadow-md">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-teal-100 text-xs font-medium uppercase tracking-wider">Total Jenis Barang</p>
                        <h3 class="text-2xl font-black mt-1"><?= $total_produk ?> <span class="text-sm font-normal text-teal-200">Item</span></h3>
                    </div>
                    <div class="bg-white/10 p-3 rounded-xl text-xl">📦</div>
                </div>
            </div>

            <!-- Stok Menipis -->
            <div class="bg-gradient-to-r from-impian-orange to-red-600 rounded-xl p-5 text-white shadow-md">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-orange-100 text-xs font-medium uppercase tracking-wider">Stok Menipis (&le; 3)</p>
                        <h3 class="text-2xl font-black mt-1"><?= $stok_menipis_count ?> <span class="text-sm font-normal text-orange-200">Item</span></h3>
                    </div>
                    <div class="bg-white/10 p-3 rounded-xl text-xl">⚠️</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- ================= KOLOM KIRI: FORM SWITCHER ================= -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- NAVIGATION TABS FORM -->
                <div class="grid grid-cols-2 gap-2 bg-slate-200/80 p-1.5 rounded-xl border border-slate-300">
                    <button id="btnFormProduct" onclick="switchFormTab('product')" class="py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 bg-white text-impian-navy shadow-sm">
                        <span>➕</span> Tambah Barang
                    </button>
                    <button id="btnFormUser" onclick="switchFormTab('user')" class="py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                        <span>👤</span> Tambah User
                    </button>
                </div>

                <!-- FORM 1: TAMBAH PRODUK BARU -->
                <div id="formProduct" class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3.5">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <span>📦</span> Tambah Produk Baru
                        </h3>
                    </div>
                    <form action="" method="POST" class="p-5 space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Barang / Barcode</label>
                            <input type="text" name="kode_barang" placeholder="Contoh: BRG001" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Barang</label>
                            <input type="text" name="nama_barang" placeholder="Contoh: Kopi Hitam 200ml" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kategori</label>
                            <select name="category_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                $cat_res = mysqli_query($koneksi, $categories_query);
                                while ($c = mysqli_fetch_assoc($cat_res)): 
                                ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Harga Beli (HPP)</label>
                                <input type="number" name="harga_beli" placeholder="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Harga Jual</label>
                                <input type="number" name="harga_jual" placeholder="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Stok Awal</label>
                            <input type="number" name="stok" placeholder="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" required>
                        </div>
                        <button type="submit" name="tambah_produk" class="w-full bg-impian-orange hover:bg-orange-600 text-white font-bold py-2.5 rounded-lg transition shadow-md flex items-center justify-center gap-2 text-xs">
                            <span>💾</span> Simpan Produk
                        </button>
                    </form>
                </div>

                <!-- FORM 2: TAMBAH USER / KASIR BARU -->
                <div id="formUser" class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3.5">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <span>👤</span> Tambah User / Kasir Baru
                        </h3>
                    </div>
                    <form action="" method="POST" class="p-5 space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama" placeholder="Contoh: Ahmad Kasir" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-teal focus:border-transparent transition" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Username</label>
                            <input type="text" name="username" placeholder="Contoh: ahmad123" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-teal focus:border-transparent transition" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Password</label>
                            <input type="password" name="password" placeholder="Masukkan password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-teal focus:border-transparent transition" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Role / Hak Akses</label>
                            <select name="role" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-impian-teal focus:border-transparent transition" required>
                                <option value="kasir">Kasir</option>
                                <option value="owner">Owner / Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah_user" class="w-full bg-impian-teal hover:bg-teal-700 text-white font-bold py-2.5 rounded-lg transition shadow-md flex items-center justify-center gap-2 text-xs">
                            <span>👤</span> Simpan User Baru
                        </button>
                    </form>
                </div>

            </div>

            <!-- ================= KOLOM KANAN: TABEL SWITCHER ================= -->
            <div class="lg:col-span-8 space-y-4">
                
                <!-- NAVIGATION TABS -->
                <div class="grid grid-cols-2 gap-2 bg-slate-200/80 p-1.5 rounded-xl border border-slate-300">
                    <button id="btnTabProduct" onclick="switchTab('product')" class="py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 bg-white text-impian-navy shadow-sm">
                        <span>📦</span> Stok Barang
                    </button>
                    <button id="btnTabUser" onclick="switchTab('user')" class="py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900">
                        <span>👥</span> Daftar User / Kasir
                    </button>
                </div>

                <!-- TAB 1: TABEL INVENTARIS PRODUK -->
                <div id="tabProduct" class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <span>📦</span> Daftar Stok Produk
                        </h3>
                        <!-- SEARCH BAR PRODUK -->
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 text-xs">🔍</span>
                            <input type="text" id="searchProduct" onkeyup="filterProducts()" placeholder="Cari kode / nama barang..." class="w-full bg-white border border-slate-300 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition shadow-sm">
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
                        <table class="w-full text-left text-xs text-slate-600" id="tableProduct">
                            <thead class="bg-slate-100 uppercase font-semibold text-slate-700 border-b border-slate-200 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama Barang</th>
                                    <th class="px-4 py-3">Harga Beli</th>
                                    <th class="px-4 py-3">Harga Jual</th>
                                    <th class="px-4 py-3">Stok</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (!empty($products_list)): ?>
                                    <?php foreach ($products_list as $row): ?>
                                        <tr class="product-row hover:bg-slate-50 transition">
                                            <td class="px-4 py-3 font-mono font-medium text-slate-500 product-code"><?= $row['kode_barang'] ?></td>
                                            <td class="px-4 py-3 font-semibold text-slate-800 product-name"><?= $row['nama_barang'] ?></td>
                                            <td class="px-4 py-3">Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                                            <td class="px-4 py-3 font-medium text-slate-800">Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                                            <td class="px-4 py-3">
                                                <?php if ($row['stok'] <= 3): ?>
                                                    <span class="inline-block bg-red-100 text-red-700 px-2.5 py-0.5 rounded-full font-bold text-[11px] border border-red-200"><?= $row['stok'] ?> (Menipis)</span>
                                                <?php else: ?>
                                                    <span class="inline-block bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold text-[11px] border border-emerald-200"><?= $row['stok'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center space-x-1">
                                                <button onclick="openModal('modalEditProduct<?= $row['id'] ?>')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-2.5 py-1 rounded transition text-[11px]">Edit</button>
                                                <a href="admin.php?hapus=<?= $row['id'] ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold px-2.5 py-1 rounded transition text-[11px] inline-block" onclick="return confirm('Yakin ingin menghapus barang ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-slate-400 py-6">Belum ada data produk.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: TABEL KELOLA USER / KASIR -->
                <div id="tabUser" class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <span>👥</span> Daftar Pengguna / Kasir
                            </h3>
                            <!-- BADGE TOTAL USER -->
                            <div class="flex items-center gap-1.5 text-[11px] font-semibold">
                                <span class="bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full border border-slate-300">
                                    Semua: <?= $total_semua ?>
                                </span>
                                <span class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200">
                                    Kasir: <?= $total_kasir ?>
                                </span>
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">
                                    Owner: <?= $total_owner ?>
                                </span>
                            </div>
                        </div>

                        <!-- SEARCH BAR USER -->
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400 text-xs">🔍</span>
                            <input type="text" id="searchUser" onkeyup="filterUsers()" placeholder="Cari nama / username..." class="w-full bg-white border border-slate-300 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-impian-teal focus:border-transparent transition shadow-sm">
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
                        <table class="w-full text-left text-xs text-slate-600" id="tableUser">
                            <thead class="bg-slate-100 uppercase font-semibold text-slate-700 border-b border-slate-200 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Nama Lengkap</th>
                                    <th class="px-4 py-3">Username</th>
                                    <th class="px-4 py-3">Role</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (!empty($users_list)): ?>
                                    <?php foreach ($users_list as $usr): ?>
                                        <tr class="user-row hover:bg-slate-50 transition">
                                            <td class="px-4 py-3 font-mono text-slate-500">#<?= $usr['id'] ?></td>
                                            <td class="px-4 py-3 font-bold text-slate-800 user-fullname"><?= $usr['nama'] ?></td>
                                            <td class="px-4 py-3 user-username"><?= $usr['username'] ?></td>
                                            <td class="px-4 py-3">
                                                <?php if ($usr['role'] === 'owner'): ?>
                                                    <span class="inline-block bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full font-bold text-[11px] border border-blue-200">Owner</span>
                                                <?php else: ?>
                                                    <span class="inline-block bg-teal-100 text-teal-700 px-2.5 py-0.5 rounded-full font-bold text-[11px] border border-teal-200">Kasir</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center space-x-1">
                                                <button onclick="openModal('modalEditUser<?= $usr['id'] ?>')" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-2.5 py-1 rounded transition text-[11px]">Edit</button>
                                                <?php if ($usr['id'] != $_SESSION['user_id']): ?>
                                                    <a href="admin.php?hapus_user=<?= $usr['id'] ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold px-2.5 py-1 rounded transition text-[11px] inline-block" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                                <?php else: ?>
                                                    <button class="bg-slate-300 text-slate-600 font-bold px-2.5 py-1 rounded text-[11px] cursor-not-allowed" disabled>Saya</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-slate-400 py-6">Belum ada user.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ================= SECTION MODAL (Ditaruh luar tabel agar penataan DOM & Backdrop Z-Index Normal) ================= -->

    <!-- MODALS EDIT PRODUK -->
    <?php foreach ($products_list as $row): ?>
        <div id="modalEditProduct<?= $row['id'] ?>" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
                <div class="bg-impian-navy px-5 py-3.5 text-white flex justify-between items-center">
                    <h3 class="font-bold text-sm">Edit Produk</h3>
                    <button onclick="closeModal('modalEditProduct<?= $row['id'] ?>')" class="text-white/70 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form action="" method="POST" class="p-5 space-y-3 text-xs text-left">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Kode Barang / Barcode</label>
                        <input type="text" name="kode_barang" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="<?= $row['kode_barang'] ?>" required>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Nama Barang</label>
                        <input type="text" name="nama_barang" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="<?= $row['nama_barang'] ?>" required>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Kategori</label>
                        <select name="category_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" required>
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
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Harga Beli (HPP)</label>
                            <input type="number" name="harga_beli" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="<?= $row['harga_beli'] ?>" required>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Harga Jual</label>
                            <input type="number" name="harga_jual" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="<?= $row['harga_jual'] ?>" required>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Stok</label>
                        <input type="number" name="stok" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="<?= $row['stok'] ?>" required>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('modalEditProduct<?= $row['id'] ?>')" class="px-4 py-2 bg-slate-200 text-slate-700 font-semibold rounded-lg hover:bg-slate-300 transition">Batal</button>
                        <button type="submit" name="edit_produk" class="px-4 py-2 bg-impian-orange hover:bg-orange-600 text-white font-bold rounded-lg transition shadow">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- MODALS EDIT USER -->
    <?php foreach ($users_list as $usr): ?>
        <div id="modalEditUser<?= $usr['id'] ?>" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
                <div class="bg-impian-navy px-5 py-3.5 text-white flex justify-between items-center">
                    <h3 class="font-bold text-sm">Edit User / Kasir</h3>
                    <button onclick="closeModal('modalEditUser<?= $usr['id'] ?>')" class="text-white/70 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form action="" method="POST" class="p-5 space-y-3 text-xs text-left">
                    <input type="hidden" name="id_user" value="<?= $usr['id'] ?>">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-teal" value="<?= $usr['nama'] ?>" required>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Username</label>
                        <input type="text" name="username" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-teal" value="<?= $usr['username'] ?>" required>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Password Baru <span class="text-slate-400 font-normal">(Kosongkan jika tidak diganti)</span></label>
                        <input type="password" name="password" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-teal" placeholder="Password Baru">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Role / Hak Akses</label>
                        <select name="role" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-teal" required>
                            <option value="kasir" <?= $usr['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                            <option value="owner" <?= $usr['role'] === 'owner' ? 'selected' : '' ?>>Owner / Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('modalEditUser<?= $usr['id'] ?>')" class="px-4 py-2 bg-slate-200 text-slate-700 font-semibold rounded-lg hover:bg-slate-300 transition">Batal</button>
                        <button type="submit" name="edit_user" class="px-4 py-2 bg-impian-teal hover:bg-teal-700 text-white font-bold rounded-lg transition shadow">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- JS untuk Modal Popup, Live Filter & Tab Switcher -->
    <script>
        function switchTab(type) {
            const tabProduct = document.getElementById('tabProduct');
            const tabUser = document.getElementById('tabUser');
            const btnProduct = document.getElementById('btnTabProduct');
            const btnUser = document.getElementById('btnTabUser');

            if (type === 'product') {
                tabProduct.classList.remove('hidden');
                tabUser.classList.add('hidden');
                
                btnProduct.className = "py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 bg-white text-impian-navy shadow-sm";
                btnUser.className = "py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900";
            } else {
                tabUser.classList.remove('hidden');
                tabProduct.classList.add('hidden');

                btnUser.className = "py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 bg-white text-impian-navy shadow-sm";
                btnProduct.className = "py-2 px-4 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900";
            }
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function filterProducts() {
            const input = document.getElementById('searchProduct').value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');

            rows.forEach(row => {
                const code = row.querySelector('.product-code').textContent.toLowerCase();
                const name = row.querySelector('.product-name').textContent.toLowerCase();

                if (code.includes(input) || name.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterUsers() {
            const input = document.getElementById('searchUser').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                const fullname = row.querySelector('.user-fullname').textContent.toLowerCase();
                const username = row.querySelector('.user-username').textContent.toLowerCase();

                if (fullname.includes(input) || username.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function switchFormTab(type) {
            const formProduct = document.getElementById('formProduct');
            const formUser = document.getElementById('formUser');
            const btnProduct = document.getElementById('btnFormProduct');
            const btnUser = document.getElementById('btnFormUser');

            if (type === 'product') {
                formProduct.classList.remove('hidden');
                formUser.classList.add('hidden');
                
                btnProduct.className = "py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 bg-white text-impian-navy shadow-sm";
                btnUser.className = "py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900";
            } else {
                formUser.classList.remove('hidden');
                formProduct.classList.add('hidden');

                btnUser.className = "py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 bg-white text-impian-navy shadow-sm";
                btnProduct.className = "py-2 px-3 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900";
            }
        }
    </script>

</body>
</html>