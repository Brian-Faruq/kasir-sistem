<?php
session_start();
require_once 'koneksi.php';

// 1. CEK AUTOLOGIN DARI COOKIE (Ingat Saya)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id']) && isset($_COOKIE['key'])) {
    $user_id = intval($_COOKIE['user_id']);
    $key     = $_COOKIE['key'];

    $q_cookie = mysqli_query($koneksi, "SELECT * FROM users WHERE id = $user_id");
    if ($row = mysqli_fetch_assoc($q_cookie)) {
        // Verifikasi key cookie berdasarkan username
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

    // Enkripsi inputan password dengan MD5
    $password_md5 = md5($password);

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' AND password = '$password_md5'");

    if (mysqli_num_rows($query) === 1) {
        $row = mysqli_fetch_assoc($query);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['nama']    = $row['nama'];
        $_SESSION['role']    = $row['role'];

        // FITUR INGAT SAYA (REMEMBER ME)
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
    <title>Login - POS UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

<div class="card shadow-sm style-card" style="width: 100%; max-width: 400px;">
    <div class="card-body p-4">
        <h3 class="card-title text-center mb-4 fw-bold text-primary">POS UMKM</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            
            <!-- CHECKBOX INGAT SAYA -->
            <div class="mb-3 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label small" for="remember">Ingat Saya</label>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold">LOGIN</button>
        </form>
    </div>
</div>

</body>
</html>