<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

// Set default date range (current month)
$bulan_ini = date('Y-m-01');
$hari_ini = date('Y-m-d');

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : $bulan_ini;
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : $hari_ini;
$jenis_laporan = isset($_GET['jenis']) ? $_GET['jenis'] : 'peminjaman';

// Get statistics for the selected period
// 1. Total borrowings
$borrowings_query = "SELECT COUNT(*) as total FROM peminjaman 
                    WHERE tanggal_pinjam BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
$borrowings_result = mysqli_query($koneksi, $borrowings_query);
$total_peminjaman = mysqli_fetch_assoc($borrowings_result)['total'];

// 2. Total returns
$returns_query = "SELECT COUNT(*) as total FROM peminjaman 
                 WHERE tanggal_kembali BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
$returns_result = mysqli_query($koneksi, $returns_query);
$total_pengembalian = mysqli_fetch_assoc($returns_result)['total'];

// 3. Total penalties
$penalties_query = "SELECT SUM(jumlah) as total FROM denda 
                   WHERE tanggal_dibuat BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
$penalties_result = mysqli_query($koneksi, $penalties_query);
$total_denda = mysqli_fetch_assoc($penalties_result)['total'] ?: 0;

// 4. Total new members
$members_query = "SELECT COUNT(*) as total FROM pengguna 
                 WHERE tanggal_daftar BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' AND peran = 'anggota'";
$members_result = mysqli_query($koneksi, $members_query);
$total_anggota_baru = mysqli_fetch_assoc($members_result)['total'];

// Get detailed report data based on selected report type
if ($jenis_laporan == 'peminjaman') {
    $report_query = "SELECT p.*, b.judul as judul_buku, u.nama as nama_pengguna 
                    FROM peminjaman p 
                    JOIN buku b ON p.id_buku = b.id 
                    JOIN pengguna u ON p.id_pengguna = u.id 
                    WHERE p.tanggal_pinjam BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' 
                    ORDER BY p.tanggal_pinjam DESC";
} else if ($jenis_laporan == 'pengembalian') {
    $report_query = "SELECT p.*, b.judul as judul_buku, u.nama as nama_pengguna 
                    FROM peminjaman p 
                    JOIN buku b ON p.id_buku = b.id 
                    JOIN pengguna u ON p.id_pengguna = u.id 
                    WHERE p.tanggal_kembali BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' 
                    AND p.status = 'dikembalikan' 
                    ORDER BY p.tanggal_kembali DESC";
} else if ($jenis_laporan == 'denda') {
    $report_query = "SELECT d.*, p.id as id_peminjaman, b.judul as judul_buku, u.nama as nama_pengguna 
                    FROM denda d 
                    JOIN peminjaman p ON d.id_peminjaman = p.id 
                    JOIN buku b ON p.id_buku = b.id 
                    JOIN pengguna u ON d.id_pengguna = u.id 
                    WHERE d.tanggal_dibuat BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' 
                    ORDER BY d.tanggal_dibuat DESC";
} else if ($jenis_laporan == 'anggota') {
    $report_query = "SELECT * FROM pengguna 
                    WHERE tanggal_daftar BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' 
                    AND peran = 'anggota' 
                    ORDER BY tanggal_daftar DESC";
} else if ($jenis_laporan == 'buku_populer') {
    $report_query = "SELECT b.judul, b.penulis, COUNT(p.id) as jumlah_peminjaman 
                    FROM peminjaman p 
                    JOIN buku b ON p.id_buku = b.id 
                    WHERE p.tanggal_pinjam BETWEEN '$tanggal_mulai' AND '$tanggal_akhir' 
                    GROUP BY p.id_buku 
                    ORDER BY jumlah_peminjaman DESC 
                    LIMIT 10";
}

$report_result = mysqli_query($koneksi, $report_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Laporan</h1>
                <button onclick="printReport()" class="btn btn-primary">Cetak Laporan</button>
            </div>

            <div class="report-filters">
                <form method="get" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>">
                        </div>

                        <div class="form-group">
                            <label for="tanggal_akhir">Tanggal Akhir</label>
                            <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                        </div>

                        <div class="form-group">
                            <label for="jenis">Jenis Laporan</label>
                            <select id="jenis" name="jenis">
                                <option value="peminjaman" <?php echo $jenis_laporan == 'peminjaman' ? 'selected' : ''; ?>>Peminjaman</option>
                                <option value="pengembalian" <?php echo $jenis_laporan == 'pengembalian' ? 'selected' : ''; ?>>Pengembalian</option>
                                <option value="denda" <?php echo $jenis_laporan == 'denda' ? 'selected' : ''; ?>>Denda</option>
                                <option value="anggota" <?php echo $jenis_laporan == 'anggota' ? 'selected' : ''; ?>>Anggota Baru</option>
                                <option value="buku_populer" <?php echo $jenis_laporan == 'buku_populer' ? 'selected' : ''; ?>>Buku Populer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-secondary">Terapkan Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="printable-report">
                <div class="report-header">
                    <h2>Laporan <?php echo ucfirst(str_replace('_', ' ', $jenis_laporan)); ?></h2>
                    <p>Periode: <?php echo date('d M Y', strtotime($tanggal_mulai)); ?> - <?php echo date('d M Y', strtotime($tanggal_akhir)); ?></p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(74, 111, 165, 0.1);">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>Total Peminjaman</h3>
                            <p class="stat-number"><?php echo $total_peminjaman; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(39, 174, 96, 0.1);">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #27ae60;">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>Total Pengembalian</h3>
                            <p class="stat-number"><?php echo $total_pengembalian; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(231, 76, 60, 0.1);">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #e74c3c;">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>Total Denda</h3>
                            <p class="stat-number">Rp <?php echo number_format($total_denda); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(230, 126, 34, 0.1);">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #e67e22;">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <h3>Anggota Baru</h3>
                            <p class="stat-number"><?php echo $total_anggota_baru; ?></p>
                        </div>
                    </div>
                </div>

                <div class="admin-table-container">
                    <?php if ($jenis_laporan == 'peminjaman'): ?>
                        <h3>Detail Peminjaman</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Pinjam</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                            <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                                            <td><?php echo ucfirst($row['status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Tidak ada data peminjaman untuk periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($jenis_laporan == 'pengembalian'): ?>
                        <h3>Detail Pengembalian</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Kembali</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_kembali'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                            <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Tidak ada data pengembalian untuk periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($jenis_laporan == 'denda'): ?>
                        <h3>Detail Denda</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_dibuat'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                            <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                            <td>Rp <?php echo number_format($row['jumlah']); ?></td>
                                            <td><?php echo $row['status'] == 'dibayar' ? 'Dibayar' : 'Belum Dibayar'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Tidak ada data denda untuk periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($jenis_laporan == 'anggota'): ?>
                        <h3>Detail Anggota Baru</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Daftar</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_daftar'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">Tidak ada anggota baru untuk periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($jenis_laporan == 'buku_populer'): ?>
                        <h3>Buku Populer</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Judul</th>
                                    <th>Penulis</th>
                                    <th>Jumlah Peminjaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($report_result) > 0): ?>
                                    <?php $rank = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($report_result)): ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                            <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                                            <td><?php echo $row['jumlah_peminjaman']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">Tidak ada data peminjaman buku untuk periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
        .report-filters {
            background-color: white;
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .filter-form .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
        }

        .report-header {
            margin-bottom: 20px;
        }

        .report-header h2 {
            margin-bottom: 5px;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #printable-report,
            #printable-report * {
                visibility: visible;
            }

            #printable-report {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .admin-header,
            .report-filters {
                display: none;
            }
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
        }
    </style>

    <script>
        function printReport() {
            window.print();
        }
    </script>
</body>

</html>