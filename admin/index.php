<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

// Get statistics
$books_query = "SELECT COUNT(*) as jumlah FROM buku";
$books_result = mysqli_query($koneksi, $books_query);
$jumlah_buku = mysqli_fetch_assoc($books_result)['jumlah'];

$users_query = "SELECT COUNT(*) as jumlah FROM pengguna WHERE peran = 'anggota'";
$users_result = mysqli_query($koneksi, $users_query);
$jumlah_anggota = mysqli_fetch_assoc($users_result)['jumlah'];

$borrowed_query = "SELECT COUNT(*) as jumlah FROM peminjaman WHERE status = 'dipinjam'";
$borrowed_result = mysqli_query($koneksi, $borrowed_query);
$jumlah_dipinjam = mysqli_fetch_assoc($borrowed_result)['jumlah'];

$overdue_query = "SELECT COUNT(*) as jumlah FROM peminjaman WHERE status = 'dipinjam' AND tanggal_jatuh_tempo < CURDATE()";
$overdue_result = mysqli_query($koneksi, $overdue_query);
$jumlah_terlambat = mysqli_fetch_assoc($overdue_result)['jumlah'];

// Get recent borrowings
$recent_query = "SELECT b.judul, p.nama as nama_anggota, pm.tanggal_pinjam, pm.tanggal_jatuh_tempo 
                FROM peminjaman pm 
                JOIN buku b ON pm.id_buku = b.id 
                JOIN pengguna p ON pm.id_pengguna = p.id 
                ORDER BY pm.tanggal_pinjam DESC LIMIT 5";
$recent_result = mysqli_query($koneksi, $recent_query);

// Get popular books
$popular_query = "SELECT b.judul, COUNT(pm.id) as jumlah_pinjam 
                 FROM peminjaman pm 
                 JOIN buku b ON pm.id_buku = b.id 
                 GROUP BY pm.id_buku 
                 ORDER BY jumlah_pinjam DESC LIMIT 5";
$popular_result = mysqli_query($koneksi, $popular_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard Admin</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_pengguna']); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon books-icon"></div>
                    <div class="stat-info">
                        <h3>Total Buku</h3>
                        <p class="stat-number"><?php echo $jumlah_buku; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon users-icon"></div>
                    <div class="stat-info">
                        <h3>Total Anggota</h3>
                        <p class="stat-number"><?php echo $jumlah_anggota; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon borrowed-icon"></div>
                    <div class="stat-info">
                        <h3>Buku Dipinjam</h3>
                        <p class="stat-number"><?php echo $jumlah_dipinjam; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon overdue-icon"></div>
                    <div class="stat-info">
                        <h3>Buku Terlambat</h3>
                        <p class="stat-number"><?php echo $jumlah_terlambat; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="admin-grid">
                <div class="admin-card">
                    <h2>Peminjaman Terbaru</h2>
                    <?php if (mysqli_num_rows($recent_result) > 0): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Buku</th>
                                    <th>Anggota</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_anggota']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Tidak ada peminjaman terbaru.</p>
                    <?php endif; ?>
                    <div class="admin-card-footer">
                        <a href="peminjaman.php" class="btn btn-small">Lihat Semua</a>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h2>Buku Populer</h2>
                    <?php if (mysqli_num_rows($popular_result) > 0): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Buku</th>
                                    <th>Jumlah Pinjam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($popular_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                        <td><?php echo $row['jumlah_pinjam']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Tidak ada data peminjaman tersedia.</p>
                    <?php endif; ?>
                    <div class="admin-card-footer">
                        <a href="buku.php" class="btn btn-small">Kelola Buku</a>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2>Aksi Cepat</h2>
                <div class="action-buttons">
                    <a href="buku.php?aksi=tambah" class="btn btn-primary">Tambah Buku Baru</a>
                    <a href="kategori.php?aksi=tambah" class="btn btn-secondary">Tambah Kategori</a>
                    <a href="anggota.php" class="btn btn-secondary">Kelola Anggota</a>
                    <a href="peminjaman.php?filter=terlambat" class="btn btn-secondary">Lihat Buku Terlambat</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
