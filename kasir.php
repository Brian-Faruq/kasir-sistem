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

$products_list = mysqli_query($koneksi, "SELECT * FROM products WHERE stok > 0 ORDER BY nama_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - POS UMKM</title>
    
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

<!-- NAVBAR (z-index 10 - akan tertutup overlay saat modal dibuka) -->
<nav class="bg-gradient-to-r from-impian-orange to-impian-amber shadow-lg mb-4 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <span class="text-white font-extrabold text-xl tracking-wider">POS SEKOLAH IMPIAN</span>
        </div>
        <div class="flex items-center space-x-4 text-white text-sm">
            <span>Halo, <strong class="font-bold"><?= $_SESSION['nama'] ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
            <?php if ($_SESSION['role'] === 'owner'): ?>
                <a href="admin.php" class="border border-white/40 hover:bg-white/10 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">Dashboard Owner</a>
            <?php endif; ?>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition shadow">Logout</a>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4 relative">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- KOLOM KIRI: KERANJANG BELANJA / TABEL (z-index 30 - TETAP TERANG DI ATAS OVERLAY) -->
        <div class="lg:col-span-7 relative z-30">
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
                <div class="bg-white border-b border-slate-200 px-5 py-3.5 flex justify-between items-center">
                    <span class="font-bold text-impian-navy text-lg">Keranjang Belanja</span>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="kasir.php?batal=1" class="text-xs text-red-600 hover:text-red-800 font-bold border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition" onclick="return confirm('Kosongkan keranjang belanja?')">Kosongkan Keranjang</a>
                    <?php endif; ?>
                </div>

                <!-- TABEL KERANJANG -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase text-xs border-b border-slate-200">
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
                                    <td class="py-3 px-4 text-slate-600"><?= $item['qty'] ?></td>
                                    <td class="py-3 px-4 font-bold text-slate-800">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="kasir.php?hapus=<?= $id_p ?>" class="bg-red-100 text-red-600 hover:bg-red-200 text-xs px-2.5 py-1 rounded-md font-bold transition">Hapus</a>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-slate-400 py-8">Keranjang belanja kosong</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: PILIH BARANG & PEMBAYARAN (z-index 10 - AKAN GELAP TERKAPUT OVERLAY) -->
        <div class="lg:col-span-5 space-y-4 relative z-10">
            
            <!-- CARD 1: PILIH BARANG -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
                <div class="bg-impian-navy px-5 py-3 flex justify-between items-center text-white">
                    <h2 class="font-bold text-base">Pilih Barang</h2>
                    <button type="button" onclick="openScanner()" class="bg-impian-orange hover:bg-orange-600 text-white text-xs px-3 py-1.5 rounded-lg font-semibold flex items-center gap-1 transition shadow">
                        📷 Scan Barcode
                    </button>
                </div>
                <div class="p-4">
                    <form action="" method="POST">
                        <div class="grid grid-cols-12 gap-3 mb-3">
                            <div class="col-span-8">
                                <label class="block text-xs font-bold text-slate-600 mb-1">Nama / Kode Barang</label>
                                <select name="product_id" id="select-produk" class="w-full" required>
                                    <option value="">-- Cari Produk --</option>
                                    <?php while ($p = mysqli_fetch_assoc($products_list)): ?>
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
                        <button type="submit" name="tambah_keranjang" id="btn_tambah_keranjang" class="w-full bg-impian-orange hover:bg-orange-600 text-white font-bold py-2 rounded-lg transition shadow">
                            + Tambah Ke Keranjang
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD 2: PEMBAYARAN -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200 p-4">
                <form action="" method="POST" onsubmit="return verifikasiPembayaran()">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                        
                        <!-- SISI KIRI PEMBAYARAN -->
                        <div class="space-y-3">
                            <div class="bg-slate-50 py-3 px-3 rounded-lg border border-slate-100 text-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Total Belanja</span>
                                <h3 class="text-2xl font-black text-impian-orange">Rp <?= number_format($grand_total, 0, ',', '.') ?></h3>
                                <input type="hidden" id="grand_total" value="<?= $grand_total ?>">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Metode Pembayaran</label>
                                <select name="metode_bayar" id="metode_bayar" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-impian-orange" onchange="toggleMetodeBayar()">
                                    <option value="cash">CASH (Tunai)</option>
                                    <option value="qris">QRIS (Nontunai)</option>
                                </select>
                            </div>
                        </div>

                        <!-- SISI KANAN PEMBAYARAN -->
                        <div class="space-y-3">
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <div id="quick_cash_container" class="grid grid-cols-5 gap-1">
                                    <button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-xs font-bold py-1.5 rounded-md text-slate-700 text-center" onclick="setNominal(<?= $grand_total ?>)">Pas</button>
                                    <button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-xs font-bold py-1.5 rounded-md text-slate-700 text-center" onclick="setNominal(10000)">10k</button>
                                    <button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-xs font-bold py-1.5 rounded-md text-slate-700 text-center" onclick="setNominal(20000)">20k</button>
                                    <button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-xs font-bold py-1.5 rounded-md text-slate-700 text-center" onclick="setNominal(50000)">50k</button>
                                    <button type="button" class="bg-slate-100 border border-slate-300 hover:bg-slate-200 text-xs font-bold py-1.5 rounded-md text-slate-700 text-center" onclick="setNominal(100000)">100k</button>
                                </div>
                            <?php endif; ?>

                            <div>
                                <input type="number" name="bayar" id="input_bayar" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-base font-bold focus:outline-none focus:ring-2 focus:ring-impian-orange" placeholder="Nominal Uang Bayar" required>
                            </div>

                            <button type="submit" name="proses_transaksi" class="w-full bg-impian-teal hover:bg-impian-darkteal text-white font-bold py-2.5 rounded-lg shadow transition text-xs tracking-wide uppercase <?= empty($_SESSION['cart']) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                                PROSES TRANSAKSI
                            </button>
                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>
</div>

<!-- MODAL TAILWIND CAMERA SCANNER (z-index 50 - DI PALING ATAS) -->
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
            if (quickCash) quickCash.style.display = 'none';
        } else {
            inputBayar.value = '';
            inputBayar.readOnly = false;
            if (quickCash) quickCash.style.display = 'grid';
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