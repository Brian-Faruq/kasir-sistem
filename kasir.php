<?php
session_start();
require_once 'koneksi.php';

// Proteksi Halaman Kasir
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Inisialisasi Keranjang Belanja
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
            if ($qty > $prod['stok']) {
                echo "<script>alert('Stok tidak mencukupi! Stok tersisa: {$prod['stok']}');</script>";
            } else {
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

// 2. HAPUS SATU BARANG DARI KERANJANG
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    unset($_SESSION['cart'][$id_hapus]);
    header("Location: kasir.php");
    exit;
}

// 3. KOSONGKAN SELURUH KERANJANG
if (isset($_GET['batal'])) {
    $_SESSION['cart'] = [];
    header("Location: kasir.php");
    exit;
}

// 4. PROSES TRANSAKSI
if (isset($_POST['proses_transaksi'])) {
    $bayar        = floatval($_POST['bayar']);
    $metode_bayar = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);
    $user_id      = $_SESSION['user_id'];

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

        $q_trans = "INSERT INTO transactions (no_nota, user_id, total_harga, bayar, kembalian, metode_bayar) 
                    VALUES ('$no_nota', $user_id, $total_harga, $bayar, $kembalian, '$metode_bayar')";
        
        if (mysqli_query($koneksi, $q_trans)) {
            $transaction_id = mysqli_insert_id($koneksi);

            foreach ($_SESSION['cart'] as $p_id => $item) {
                $subtotal = $item['harga_jual'] * $item['qty'];
                $h_beli   = $item['harga_beli'];
                $h_jual   = $item['harga_jual'];
                $qty_item = $item['qty'];

                mysqli_query($koneksi, "INSERT INTO transaction_details (transaction_id, product_id, qty, harga_beli, harga_jual, subtotal) 
                                        VALUES ($transaction_id, $p_id, $qty_item, $h_beli, $h_jual, $subtotal)");

                mysqli_query($koneksi, "UPDATE products SET stok = stok - $qty_item WHERE id = $p_id");
            }

            $_SESSION['cart'] = [];
            header("Location: cetak_nota.php?id=" . $transaction_id);
            exit;
        }
    }
}

// AMBIL RINGKASAN PRODUK READY
$q_total_prod = mysqli_query($koneksi, "SELECT COUNT(id) as total FROM products WHERE stok > 0");
$total_prod   = mysqli_fetch_assoc($q_total_prod)['total'] ?? 0;

$products_list = mysqli_query($koneksi, "SELECT * FROM products WHERE stok > 0 ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - POS SEKOLAH IMPIAN</title>
    
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
    
    <!-- Select2 & HTML5 QRCode -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border-color: #D1D5DB !important;
            border-radius: 0.5rem !important;
            padding-top: 6px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
    </style>
</head>
<body class="bg-slate-100 font-sans min-h-screen relative">

<!-- OVERLAY BACKDROP DARK (z-index 20) -->
<div id="scannerOverlay" class="fixed inset-0 bg-black/60 z-20 hidden transition-opacity"></div>

<!-- NAVBAR -->
<nav class="bg-gradient-to-r from-impian-orange via-impian-amber to-amber-600 shadow-md mb-6 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-white/20 p-2 rounded-lg backdrop-blur-md">
                <span class="text-xl">🛒</span>
            </div>
            <div>
                <span class="text-white font-black text-lg tracking-wide block leading-none">POS SEKOLAH IMPIAN</span>
                <span class="text-white/80 text-[10px] tracking-wider uppercase font-semibold">System Kasir Modern</span>
            </div>
        </div>
        <div class="flex items-center space-x-3 text-white text-xs">
            <div class="hidden sm:block text-right mr-2">
                <span class="block text-white/70 text-[10px]">Petugas Kasir</span>
                <strong class="font-bold text-sm"><?= $_SESSION['nama'] ?></strong> (<?= ucfirst($_SESSION['role']) ?>)
            </div>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="admin.php" class="bg-white/10 hover:bg-white/20 border border-white/30 text-white px-3 py-1.5 rounded-lg font-semibold transition flex items-center gap-1">
                    📊 <span class="hidden sm:inline">Dashboard</span> Owner
                </a>
            <?php endif; ?>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg font-semibold transition shadow flex items-center gap-1">
                🚪 Logout
            </a>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- KOLOM KIRI: KERANJANG BELANJA (z-index 30) -->
        <div class="lg:col-span-7 space-y-5 relative z-30">
            
            <!-- CARD KERANJANG BELANJA -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
                <div class="bg-white border-b border-slate-200 px-5 py-3.5 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🛍️</span>
                        <span class="font-bold text-impian-navy text-base">Keranjang Belanja</span>
                    </div>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="kasir.php?batal=1" class="text-xs text-red-600 hover:text-red-800 font-bold border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition" onclick="return confirm('Kosongkan keranjang belanja?')">Kosongkan Keranjang</a>
                    <?php endif; ?>
                </div>

                <!-- TABEL KERANJANG -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold border-b border-slate-200">
                                <th class="py-3 px-4">Produk</th>
                                <th class="py-3 px-4">Harga</th>
                                <th class="py-3 px-4">Qty</th>
                                <th class="py-3 px-4">Subtotal</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            $grand_total = 0;
                            if (!empty($_SESSION['cart'])): 
                                foreach ($_SESSION['cart'] as $id_p => $item):
                                    $subtotal = $item['harga_jual'] * $item['qty'];
                                    $grand_total += $subtotal;
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 px-4 font-semibold text-slate-800"><?= $item['nama_barang'] ?></td>
                                    <td class="py-3 px-4 text-slate-600">Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 text-slate-600 font-bold"><?= $item['qty'] ?></td>
                                    <td class="py-3 px-4 font-bold text-impian-orange">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="kasir.php?hapus=<?= $id_p ?>" class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 text-xs px-2.5 py-1 rounded-md font-bold transition">Hapus</a>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-slate-400 py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-5xl mb-3">🛒</span>
                                            <p class="font-semibold text-slate-500 text-sm">Keranjang belanja masih kosong</p>
                                            <p class="text-xs text-slate-400 mt-1">Pilih atau scan barang di sebelah kanan untuk memulai</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: PILIH BARANG & PEMBAYARAN (z-index 10) -->
        <div class="lg:col-span-5 space-y-5 relative z-10">
            
            <!-- CARD 1: PILIH BARANG -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
                <!-- HEADER: Title di kiri, Badge Ready di Ujung Kanan (Flex End) -->
                <div class="bg-impian-navy px-5 py-3.5 flex justify-between items-center text-white">
                    <h2 class="font-bold text-sm tracking-wide">Pilih Produk</h2>
                    <span class="bg-white/20 text-white/90 text-[10px] font-semibold px-2.5 py-1 rounded-full border border-white/10">
                        <?= $total_prod ?> Ready
                    </span>
                </div>
                
                <div class="p-4">
                    <!-- TOMBOL SCAN BARCODE (Pindah ke Atas Input) -->
                    <button type="button" onclick="openScanner()" class="w-full mb-3 bg-impian-orange hover:bg-orange-600 text-white text-xs px-3 py-2 rounded-lg font-semibold flex items-center justify-center gap-1.5 transition shadow">
                        📷 Scan Barcode Lewat Device
                    </button>

                    <form action="" method="POST" id="form-tambah-barang">
                        <div class="grid grid-cols-12 gap-3 mb-3">
                            <div class="col-span-8">
                                <label class="block text-xs font-bold text-slate-600 mb-1">Cari Produk / Barcode</label>
                                <select name="product_id" id="select-produk" class="w-full" required>
                                    <option value="">-- Cari Produk --</option>
                                    <?php 
                                    mysqli_data_seek($products_list, 0);
                                    while ($p = mysqli_fetch_assoc($products_list)): 
                                    ?>
                                        <option value="<?= $p['id'] ?>" data-kode="<?= $p['kode_barang'] ?>" data-stok="<?= $p['stok'] ?>">
                                            <?= $p['kode_barang'] ?> - <?= $p['nama_barang'] ?> (Stok: <?= $p['stok'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-span-4">
                                <label class="block text-xs font-bold text-slate-600 mb-1">Jumlah (Qty)</label>
                                <input type="number" name="qty" id="input_qty" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange" value="1" min="1" required>
                            </div>
                        </div>
                        <button type="submit" name="tambah_keranjang" id="btn_tambah_keranjang" class="w-full bg-impian-orange hover:bg-orange-600 text-white font-bold py-2.5 rounded-lg transition shadow text-xs tracking-wide uppercase">
                            + Tambah Ke Keranjang
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD 2: PEMBAYARAN (LAYOUT PRESISI GAMBAR 2) -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200 p-4">
                <form action="" method="POST" onsubmit="return verifikasiPembayaran()">
                    <input type="hidden" id="grand_total" value="<?= $grand_total ?>">
                    
                    <div class="grid grid-cols-12 gap-4">
                        
                        <!-- SISI KIRI: TOTAL BELANJA & METODE PEMBAYARAN -->
                        <div class="col-span-5 flex flex-col justify-between">
                            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/60 text-left">
                                <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider block mb-1">TOTAL BELANJA</span>
                                <h3 class="text-2xl font-black text-impian-orange leading-tight">
                                    Rp <?= number_format($grand_total, 0, ',', '.') ?>
                                </h3>
                            </div>

                            <div class="mt-3">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Metode Pembayaran</label>
                                <select name="metode_bayar" id="metode_bayar" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-impian-orange bg-white" onchange="toggleMetodeBayar()">
                                    <option value="cash">💵 CASH (Tunai)</option>
                                    <option value="qris">📱 QRIS (Nontunai)</option>
                                </select>
                            </div>
                        </div>

                        <!-- SISI KANAN: NOMINAL CEPAT, INPUT UANG & BUTTON TRANSAKSI -->
                        <div class="col-span-7 flex flex-col justify-between space-y-2.5">
                            
                            <!-- TOMBOL NOMINAL CEPAT -->
                            <div id="quick_cash_container" class="grid grid-cols-5 gap-1">
                                <button type="button" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-[11px] font-bold py-1.5 rounded-md text-slate-700 text-center transition" onclick="setNominal(<?= $grand_total ?>)">Pas</button>
                                <button type="button" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-[11px] font-bold py-1.5 rounded-md text-slate-700 text-center transition" onclick="setNominal(10000)">10k</button>
                                <button type="button" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-[11px] font-bold py-1.5 rounded-md text-slate-700 text-center transition" onclick="setNominal(20000)">20k</button>
                                <button type="button" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-[11px] font-bold py-1.5 rounded-md text-slate-700 text-center transition" onclick="setNominal(50000)">50k</button>
                                <button type="button" class="bg-slate-50 border border-slate-200 hover:bg-slate-100 text-[11px] font-bold py-1.5 rounded-md text-slate-700 text-center transition" onclick="setNominal(100000)">100k</button>
                            </div>

                            <!-- INPUT NOMINAL UANG BAYAR -->
                            <div>
                                <input type="number" name="bayar" id="input_bayar" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-base font-bold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-impian-orange" placeholder="Nominal Uang Bayar" required>
                            </div>

                            <!-- BUTTON PROSES TRANSAKSI -->
                            <button type="submit" name="proses_transaksi" class="w-full bg-impian-teal hover:bg-impian-darkteal text-white font-black py-2.5 rounded-xl shadow transition text-xs tracking-wider uppercase <?= empty($_SESSION['cart']) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                                PROSES TRANSAKSI
                            </button>

                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>
</div>

<!-- MODAL TAILWIND CAMERA SCANNER (z-index 50) -->
<div id="modalScanner" class="fixed inset-0 z-50 hidden flex items-start justify-end p-4 sm:p-6 pt-20 pointer-events-none">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-200 pointer-events-auto">
        <div class="bg-impian-navy px-4 py-3 flex justify-between items-center text-white">
            <h3 class="font-bold text-sm">Scan Barcode Produk</h3>
            <button type="button" onclick="closeScanner()" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
        </div>
        <div class="p-4 text-center">
            <div id="reader" class="w-full rounded-lg overflow-hidden border"></div>
            <hr class="my-4">
            <div class="text-left">
                <label class="block text-xs font-bold text-slate-500 mb-1">Upload Gambar Barcode:</label>
                <input type="file" id="qr-input-file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#select-produk').select2({
            placeholder: '-- Cari Produk --',
            allowClear: true
        });

        $('#select-produk').on('change', function() {
            const stokTersedia = $(this).find(':selected').data('stok');
            const inputQty = $('#input_qty');
            if (stokTersedia !== undefined) {
                inputQty.attr('max', stokTersedia);
                if (parseInt(inputQty.val()) > stokTersedia) {
                    inputQty.val(stokTersedia);
                }
            } else {
                inputQty.removeAttr('max');
            }
        });

        if (localStorage.getItem('keepScannerOpen') === 'true') {
            localStorage.removeItem('keepScannerOpen');
            openScanner();
        }
    });

    function toggleMetodeBayar() {
        const metode = document.getElementById('metode_bayar').value;
        const grandTotal = parseFloat(document.getElementById('grand_total').value) || 0;
        const inputBayar = document.getElementById('input_bayar');
        const quickCash = document.getElementById('quick_cash_container');

        if (metode === 'qris') {
            inputBayar.value = grandTotal;
            inputBayar.readOnly = true;
            if (quickCash) quickCash.style.visibility = 'hidden';
        } else {
            inputBayar.value = '';
            inputBayar.readOnly = false;
            if (quickCash) quickCash.style.visibility = 'visible';
        }
    }

    function setNominal(amount) {
        document.getElementById('input_bayar').value = amount;
    }

    function verifikasiPembayaran() {
        const grandTotal = parseFloat(document.getElementById('grand_total').value) || 0;
        const bayar = parseFloat(document.getElementById('input_bayar').value) || 0;
        if (bayar < grandTotal) {
            alert('Uang pembayaran kurang dari total belanja!');
            return false;
        }
        return true;
    }

    let html5QrcodeScanner = null;

    function openScanner() {
        document.getElementById('modalScanner').classList.remove('hidden');
        document.getElementById('scannerOverlay').classList.remove('hidden');
        
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 220, height: 140 } };
            html5QrcodeScanner.start(
                { facingMode: "environment" }, 
                config, 
                onScanSuccess
            ).catch(err => {
                alert("Kamera tidak dapat diakses: " + err);
            });
        }
    }

    function closeScanner() {
        document.getElementById('modalScanner').classList.add('hidden');
        document.getElementById('scannerOverlay').classList.add('hidden');
        
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }).catch(err => console.log(err));
        }
    }

    let isScanning = false;

    function onScanSuccess(decodedText) {
        if (isScanning) return;
        isScanning = true;

        let found = false;
        $('#select-produk option').each(function() {
            const kodeBarang = $(this).data('kode');
            if (kodeBarang && kodeBarang.toString().trim() === decodedText.trim()) {
                $('#select-produk').val($(this).val()).trigger('change');
                found = true;
                return false;
            }
        });

        if (found) {
            $('#input_qty').val(1);
            localStorage.setItem('keepScannerOpen', 'true');
            $('#btn_tambah_keranjang').click();
        } else {
            alert("Barang dengan kode barcode: " + decodedText + " tidak ditemukan!");
            setTimeout(() => { isScanning = false; }, 1500);
        }
    }

    document.getElementById('qr-input-file').addEventListener('change', e => {
        if (e.target.files.length == 0) return;
        const imageFile = e.target.files[0];
        const html5QrCode = new Html5Qrcode("reader");
        html5QrCode.scanFile(imageFile, true)
            .then(decodedText => onScanSuccess(decodedText))
            .catch(err => alert("Gagal membaca barcode dari gambar!"));
    });
</script>

</body>
</html>