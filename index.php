<?php
session_start();
require_once 'koneksi.php';

// Jika pengguna sudah login, langsung arahkan ke halaman kasir
if (isset($_SESSION['user_id'])) {
    header("Location: kasir.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']); // Menggunakan MD5 sesuai data testing awal

    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Simpan data login ke session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        header("Location: kasir.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS UMKM</title>
    <!-- Bootstrap 5 CDN untuk tampilan cepat dan responsif -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .login-card {
            max-width: 400px;
            margin: 80px auto;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card p-4">
        <div class="card-body">
            <h3 class="text-center fw-bold mb-1">POS UMKM</h3>
            <p class="text-center text-muted mb-4">Silahkan login untuk mengakses kasir</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">MASUK</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>