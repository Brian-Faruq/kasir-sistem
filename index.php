<?php
session_start();
require_once 'koneksi.php';

// 1. CEK AUTOLOGIN DARI COOKIE (Ingat Saya)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id']) && isset($_COOKIE['key'])) {
    $user_id = intval($_COOKIE['user_id']);
    $key     = $_COOKIE['key'];

    $q_cookie = mysqli_query($koneksi, "SELECT * FROM users WHERE id = $user_id");
    if ($row = mysqli_fetch_assoc($q_cookie)) {
        if ($key === md5($row['username'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nama']    = $row['nama'];
            $_SESSION['role']    = $row['role'];
        }
    }
}

// 2. JIKA SUDAH LOGIN, REDIRECT SESUAI ROLE
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'owner') {
        header("Location: admin.php");
    } else {
        header("Location: kasir.php");
    }
    exit;
}

$error = '';

// 3. PROSES SUBMIT LOGIN
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $password_md5 = md5($password);

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' AND password = '$password_md5'");

    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['nama']    = $row['nama'];
        $_SESSION['role']    = $row['role'];

        if (isset($_POST['remember'])) {
            setcookie('user_id', $row['id'], time() + (86400 * 30), "/");
            setcookie('key', md5($row['username']), time() + (86400 * 30), "/");
        }

        if ($row['role'] === 'owner') {
            header("Location: admin.php");
        } else {
            header("Location: kasir.php");
        }
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS SEKOLAH IMPIAN</title>
    
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
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <!-- CONTAINER CARD MAIN -->
    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate-200">
        
        <!-- KOLOM KIRI: VISUAL BRANDING & PROMOSI -->
        <div class="md:col-span-5 bg-gradient-to-br from-impian-orange via-impian-amber to-amber-600 p-8 text-white flex flex-col justify-between relative overflow-hidden">
            <!-- Pattern Hiasan BG -->
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-40 h-40 bg-black/10 rounded-full blur-xl pointer-events-none"></div>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold mb-6">
                    ⚡ Point of Sale System
                </div>
                <h1 class="text-3xl font-black tracking-tight leading-tight">
                    POS SEKOLAH IMPIAN
                </h1>
                <p class="text-white/80 text-xs mt-2 leading-relaxed">
                    Sistem Kasir Terintegrasi untuk Transaksi Cepat, Akurat, dan Efisien.
                </p>
            </div>

            <!-- FITUR HIGHLIGHT -->
            <div class="my-8 space-y-3 relative z-10 hidden sm:block">
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-2.5 rounded-xl border border-white/10">
                    <span class="text-lg">📷</span>
                    <div>
                        <h4 class="text-xs font-bold">Scan Barcode Cepat</h4>
                        <p class="text-[10px] text-white/70">Support kamera & barcode reader</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm p-2.5 rounded-xl border border-white/10">
                    <span class="text-lg">💳</span>
                    <div>
                        <h4 class="text-xs font-bold">Multipayment QRIS & Cash</h4>
                        <p class="text-[10px] text-white/70">Kemudahan dalam bertransaksi</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-[11px] text-white/60">
                &copy; <?= date('Y') ?> Sekolah Impian. All rights reserved.
            </div>
        </div>

        <!-- KOLOM KANAN: FORM LOGIN -->
        <div class="md:col-span-7 p-6 sm:p-10 flex flex-col justify-center bg-white">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Selamat Datang! 👋</h2>
                <p class="text-xs text-slate-500 mt-1">Silakan masuk menggunakan akun kasir atau owner Anda.</p>
            </div>

            <!-- NOTIFIKASI ERROR -->
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded-r-lg text-xs text-red-700 font-medium">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">👤</span>
                        <input type="text" name="username" class="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">🔒</span>
                        
                        <!-- Input Password dengan Padding Kanan (pr-10) agar teks tidak tertimpa ikon mata -->
                        <input type="password" id="passwordInput" name="password" class="w-full border border-slate-300 rounded-lg pl-9 pr-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-impian-orange focus:border-transparent transition" placeholder="Masukkan password" required>
                        
                        <!-- Tombol Toggle Mata -->
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <!-- Mata Terbuka -->
                            <svg id="eyeOpen" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Mata Tertutup -->
                            <svg id="eyeClose" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.682-.763c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-1.748 1.137A9.972 9.972 0 0112 19a9.97 9.97 0 01-2.125-.225m0 0a3 3 0 01-4.243-4.243m4.243 4.243L3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="rounded text-impian-orange focus:ring-impian-orange border-slate-300">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" name="login" class="w-full bg-impian-orange hover:bg-orange-600 text-white font-bold py-2.5 rounded-lg shadow-md hover:shadow-lg transition duration-200 text-sm tracking-wide uppercase">
                    MASUK SEKARANG
                </button>
            </form>
        </div>

    </div>

    <!-- Script JS untuk Toggle Show/Hide Password -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClose = document.getElementById('eyeClose');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.remove('hidden');
                eyeClose.classList.add('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.add('hidden');
                eyeClose.classList.remove('hidden');
            }
        }
    </script>

</body>
</html>