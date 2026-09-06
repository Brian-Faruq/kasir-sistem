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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- HTML5 QRCode Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
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
        <!-- FORM PILIH BARANG -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Pilih Barang</span>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalScanner">
                        📷 Scan Barcode
                    </button>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small">Nama / Kode Barang</label>
                            <select name="product_id" id="select-produk" class="form-select" required>
                                <option value="">-- Cari Produk --</option>
                                <?php while ($p = mysqli_fetch_assoc($products_list)): ?>
                                    <option value="<?= $p['id'] ?>" data-kode="<?= $p['kode_barang'] ?>" data-stok="<?= $p['stok'] ?>">
                                        <?= $p['kode_barang'] ?> - <?= $p['nama_barang'] ?> (Stok: <?= $p['stok'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Jumlah (Qty)</label>
                            <input type="number" name="qty" id="input_qty" class="form-control" value="1" min="1" required>
                        </div>
                        <button type="submit" name="tambah_keranjang" class="btn btn-primary w-100 fw-bold">+</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- KERANJANG BELANJA & PEMBAYARAN -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Keranjang Belanja</span>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="kasir.php?batal=1" class="btn btn-outline-danger btn-sm fw-bold" onclick="return confirm('Kosongkan keranjang belanja?')">Kosongkan Keranjang</a>
                    <?php endif; ?>
                </div>
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
                    <form action="" method="POST" onsubmit="return verifikasiPembayaran()">
                        <div class="row align-items-center">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <h4 class="mb-0 fw-bold text-primary">Total: Rp <?= number_format($grand_total, 0, ',', '.') ?></h4>
                                <input type="hidden" id="grand_total" value="<?= $grand_total ?>">
                            </div>
                            <div class="col-md-7">
                                <!-- PILIH METODE PEMBAYARAN -->
                                <div class="mb-2">
                                    <label class="form-label small fw-bold mb-1">Metode Pembayaran:</label>
                                    <select name="metode_bayar" id="metode_bayar" class="form-select fw-bold" onchange="toggleMetodeBayar()">
                                        <option value="cash">CASH (Tunai)</option>
                                        <option value="qris">QRIS (Nontunai)</option>
                                    </select>
                                </div>

                                <!-- QUICK CASH BUTTONS -->
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <div id="quick_cash_container" class="btn-group w-100 mb-2" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(<?= $grand_total ?>)">Uang Pas</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(10000)">10k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(20000)">20k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(50000)">50k</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNominal(100000)">100k</button>
                                    </div>
                                <?php endif; ?>

                                <div class="input-group mb-2">
                                    <input type="number" name="bayar" id="input_bayar" class="form-control form-control-lg" placeholder="Nominal Uang Bayar" required>
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

<!-- MODAL CAMERA & FILE SCANNER -->
<div class="modal fade" id="modalScanner" tabindex="-1" aria-labelledby="modalScannerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalScannerLabel">Scan Barcode Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="reader" style="width: 100%;"></div>
                
                <hr class="my-3">
                
                <!-- OPSI UPLOAD GAMBAR BARCODE -->
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted">Atau Upload Gambar Barcode (Tanpa Kamera):</label>
                    <input type="file" id="qr-input-file" accept="image/*" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('#select-produk').select2({
            theme: 'bootstrap-5',
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
    });

    // Toggle Sesuai Metode Bayar
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
            if (quickCash) quickCash.style.display = 'flex';
        }
    }

    // Function Quick Cash Nominal
    function setNominal(amount) {
        document.getElementById('input_bayar').value = amount;
    }

    // Validasi Pembayaran Kurang
    function verifikasiPembayaran() {
        const grandTotal = parseFloat(document.getElementById('grand_total').value) || 0;
        const bayar = parseFloat(document.getElementById('input_bayar').value) || 0;

        if (bayar < grandTotal) {
            alert('Uang pembayaran kurang dari total belanja!');
            return false;
        }
        return true;
    }

    // ==========================================
    // SCRIPT INTEGRASI CAMERA BARCODE SCANNER
    // ==========================================
    let html5QrcodeScanner = null;

    const modalScanner = document.getElementById('modalScanner');
    
    modalScanner.addEventListener('shown.bs.modal', function () {
        html5QrcodeScanner = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 150 } };
        
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            config, 
            onScanSuccess
        ).catch(err => {
            alert("Kamera tidak dapat diakses: " + err);
        });
    });

    modalScanner.addEventListener('hidden.bs.modal', function () {
        stopScanner();
    });

    function stopScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }).catch(err => console.log(err));
        }
    }

    function onScanSuccess(decodedText) {
        let found = false;

        // Cari opsi di Select2 yang memiliki data-kode sama dengan hasil scan
        $('#select-produk option').each(function() {
            const kodeBarang = $(this).data('kode');
            if (kodeBarang && kodeBarang.toString().trim() === decodedText.trim()) {
                $('#select-produk').val($(this).val()).trigger('change');
                found = true;
                return false; // Break loop
            }
        });

        if (found) {
            // Tutup Modal dan matikan kamera
            const bsModal = bootstrap.Modal.getInstance(modalScanner);
            bsModal.hide();
            
            // Fokuskan ke input QTY
            setTimeout(() => {
                $('#input_qty').focus().select();
            }, 500);
        } else {
            alert("Barang dengan kode barcode: " + decodedText + " tidak ditemukan!");
        }
    }

    // Tambahan Handler untuk Upload Gambar Barcode via File Laptop
    document.getElementById('qr-input-file').addEventListener('change', e => {
        if (e.target.files.length == 0) {
            return;
        }
        const imageFile = e.target.files[0];
        const html5QrCode = new Html5Qrcode("reader");
        
        html5QrCode.scanFile(imageFile, true)
            .then(decodedText => {
                onScanSuccess(decodedText);
            })
            .catch(err => {
                alert("Gagal membaca barcode dari gambar ini. Pastikan gambar jelas!");
            });
    });
</script>

</body>
</html>