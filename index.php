<?php
session_start();
include 'config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Digital - Beranda</title>
    <link rel="icon" href="./assets/images/favicon.png" type="image/png">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <h1>Selamat Datang di Perpustakaan Digital</h1>
                <p>Temukan ribuan buku dalam genggaman Anda</p>
                <a href="katalog.php" class="btn btn-primary">Jelajahi Buku</a>
            </div>
            <div class="hero-image">
                <img src="./assets/images/hero-library.jpg" alt="Gambar Perpustakaan">
            </div>
        </section>

        <section class="featured-categories">
            <h2>Jelajahi berdasarkan Kategori</h2>
            <div class="category-grid">
                <?php
                $query = "SELECT * FROM kategori LIMIT 6";
                $result = mysqli_query($koneksi, $query);

                while ($row = mysqli_fetch_assoc($result)) {
                    echo '<a href="katalog.php?kategori=' . $row['id'] . '" class="category-card">';
                    echo '<div class="category-icon">';
                    echo '<img src="assets/images/kategori/' . $row['ikon'] . '" alt="' . $row['nama'] . '">';
                    echo '</div>';
                    echo '<h3>' . $row['nama'] . '</h3>';
                    echo '</a>';
                }
                ?>
            </div>
        </section>

        <section class="new-arrivals">
            <h2>Buku Terbaru</h2>
            <div class="book-grid">
                <?php
                $query = "SELECT b.*, k.nama as nama_kategori FROM buku b 
                          JOIN kategori k ON b.id_kategori = k.id 
                          ORDER BY b.tanggal_ditambahkan DESC LIMIT 8";
                $result = mysqli_query($koneksi, $query);

                while ($row = mysqli_fetch_assoc($result)) {
                    include 'includes/kartu-buku.php';
                }
                ?>
            </div>
            <div class="view-more">
                <a href="katalog.php" class="btn btn-secondary">Lihat Semua Buku</a>
            </div>
        </section>

        <section class="how-it-works">
            <h2>Cara Kerja</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Buat Akun</h3>
                    <p>Daftar untuk mengakses layanan perpustakaan digital kami</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Jelajahi Buku</h3>
                    <p>Telusuri koleksi buku kami yang luas</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Pinjam Buku</h3>
                    <p>Pinjam buku hanya dengan beberapa klik</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Kembalikan Tepat Waktu</h3>
                    <p>Kembalikan buku sebelum jatuh tempo untuk menghindari denda</p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>