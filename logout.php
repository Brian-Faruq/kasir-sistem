<?php
session_start();

// Hapus semua data Session
$_SESSION = [];
session_unset();
session_destroy();

// Hapus Cookie "Ingat Saya" dengan set waktu kadaluarsa ke masa lalu
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, "/");
}
if (isset($_COOKIE['key'])) {
    setcookie('key', '', time() - 3600, "/");
}

header("Location: index.php");
exit;