-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Sep 2026 pada 05.42
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kasir`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `nama_kategori`) VALUES
(1, 'Makanan'),
(2, 'Minuman'),
(3, 'Sembako');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `category_id`, `kode_barang`, `nama_barang`, `harga_beli`, `harga_jual`, `stok`, `created_at`) VALUES
(1, 1, 'BRG001', 'Indomie Goreng', 2800.00, 3500.00, 358, '2026-09-05 07:18:37'),
(2, 2, 'BRG002', 'Teh Botol Sosro', 3000.00, 4000.00, 23, '2026-09-05 07:18:37'),
(3, 3, 'BRG003', 'Minyak Goreng 1L', 14000.00, 16500.00, 20, '2026-09-05 07:18:37'),
(4, 1, 'BRG004', 'Beng-beng', 2000.00, 3000.00, 11, '2026-09-05 07:48:59'),
(5, 1, 'BRG005', 'Superstar', 500.00, 1000.00, 30, '2026-09-05 07:49:43'),
(6, 1, 'BRG006', 'Sponge', 10000.00, 16000.00, 80, '2026-09-07 13:25:07'),
(7, 2, 'BRG007', 'Kopi AA', 10000.00, 15000.00, 36, '2026-09-08 09:33:12'),
(8, 2, 'BRG008', 'le Minerale 1,5L', 4000.00, 6000.00, 100, '2026-09-08 09:34:13'),
(9, 2, 'BRG009', 'Kopi Hitam', 1000.00, 2000.00, 10, '2026-09-08 14:10:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `no_nota` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `metode_bayar` enum('cash','qris','transfer','bon') DEFAULT 'cash',
  `status_bayar` enum('lunas','belum_lunas') DEFAULT 'lunas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `no_nota`, `user_id`, `total_harga`, `bayar`, `kembalian`, `metode_bayar`, `status_bayar`, `created_at`) VALUES
(1, 'INV-20260907084105', 3, 7000.00, 7000.00, 0.00, 'cash', 'lunas', '2026-09-07 06:41:05'),
(2, 'INV-20260907085944', 3, 6000.00, 6000.00, 0.00, 'cash', 'lunas', '2026-09-07 06:59:44'),
(3, 'INV-20260907091729', 3, 10500.00, 10500.00, 0.00, 'cash', 'lunas', '2026-09-07 07:17:29'),
(4, 'INV-20260907093005', 3, 28000.00, 28000.00, 0.00, 'cash', 'lunas', '2026-09-07 07:30:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `harga_beli`, `harga_jual`, `qty`, `subtotal`) VALUES
(1, 1, 1, 2800.00, 3500.00, 2, 7000.00),
(2, 2, 4, 2000.00, 3000.00, 1, 3000.00),
(3, 2, 5, 500.00, 1000.00, 3, 3000.00),
(4, 3, 1, 2800.00, 3500.00, 3, 10500.00),
(5, 4, 5, 500.00, 1000.00, 1, 1000.00),
(6, 4, 4, 2000.00, 3000.00, 1, 3000.00),
(7, 4, 3, 14000.00, 16500.00, 1, 16500.00),
(8, 4, 1, 2800.00, 3500.00, 1, 3500.00),
(9, 4, 2, 3000.00, 4000.00, 1, 4000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','kasir') DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(3, 'Brian Faruq Androvo', 'owner', 'ae9b81e0062ae87585fea402b59e128b', 'owner', '2026-09-05 07:36:28'),
(4, 'Ahmad Fahrezy', 'kasir 01', '04efd1231a66b59323ca230c4db221e8', 'kasir', '2026-09-05 07:36:28'),
(5, 'Yusuf Baginda Akbar', 'kasir 02', 'a9e6877dd7142fa4929ff43f51859443', 'kasir', '2026-09-06 06:40:05'),
(6, 'Hisyam Annuril Falah', 'owner 02', '65f9d2697df6a0f31e804c7609168dfb', 'owner', '2026-09-09 06:39:38');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_nota` (`no_nota`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
