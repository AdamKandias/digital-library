<?php
session_start();
include 'config/koneksi.php';

// Redirect if not logged in
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: masuk.php');
    exit;
}

$id_pengguna = $_SESSION['id_pengguna'];
$tab_aktif = isset($_GET['tab']) ? $_GET['tab'] : 'dipinjam';

// Search and filter parameters
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$filter_status_denda = isset($_GET['status_denda']) ? mysqli_real_escape_string($koneksi, $_GET['status_denda']) : '';

// Get user information
$user_query = "SELECT * FROM pengguna WHERE id = $id_pengguna";
$user_result = mysqli_query($koneksi, $user_query);
$pengguna = mysqli_fetch_assoc($user_result);

// Get borrowed books
$borrowed_query = "SELECT b.*, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.id as id_peminjaman, p.status 
                  FROM peminjaman p 
                  JOIN buku b ON p.id_buku = b.id 
                  WHERE p.id_pengguna = $id_pengguna AND p.status = 'dipinjam' 
                  ORDER BY p.tanggal_jatuh_tempo ASC";
$borrowed_result = mysqli_query($koneksi, $borrowed_query);

// Get borrowing history with search and filters
$history_query = "SELECT b.*, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.tanggal_kembali, p.status 
                 FROM peminjaman p 
                 JOIN buku b ON p.id_buku = b.id 
                 WHERE p.id_pengguna = $id_pengguna AND p.status != 'dipinjam'";

if (!empty($cari)) {
    $history_query .= " AND (b.judul LIKE '%$cari%' OR b.penulis LIKE '%$cari%')";
}

if (!empty($filter_tanggal_mulai)) {
    $history_query .= " AND p.tanggal_pinjam >= '$filter_tanggal_mulai'";
}

if (!empty($filter_tanggal_akhir)) {
    $history_query .= " AND p.tanggal_pinjam <= '$filter_tanggal_akhir'";
}

if (!empty($filter_status)) {
    $history_query .= " AND p.status = '$filter_status'";
}

$history_query .= " ORDER BY p.tanggal_kembali DESC";
$history_result = mysqli_query($koneksi, $history_query);

// Get penalties with search and filters
$penalties_query = "SELECT d.*, b.judul as judul_buku, p.tanggal_jatuh_tempo 
                   FROM denda d 
                   JOIN peminjaman p ON d.id_peminjaman = p.id 
                   JOIN buku b ON p.id_buku = b.id 
                   WHERE p.id_pengguna = $id_pengguna";

if (!empty($cari)) {
    $penalties_query .= " AND b.judul LIKE '%$cari%'";
}

if (!empty($filter_tanggal_mulai)) {
    $penalties_query .= " AND d.tanggal_dibuat >= '$filter_tanggal_mulai'";
}

if (!empty($filter_tanggal_akhir)) {
    $penalties_query .= " AND d.tanggal_dibuat <= '$filter_tanggal_akhir'";
}

if (!empty($filter_status_denda)) {
    $penalties_query .= " AND d.status = '$filter_status_denda'";
}

$penalties_query .= " ORDER BY d.tanggal_dibuat DESC";
$penalties_result = mysqli_query($koneksi, $penalties_query);

// Get settings for denda
$settings_query = "SELECT * FROM pengaturan WHERE nama IN ('denda_per_hari', 'denda_buku_hilang', 'durasi_peminjaman')";
$settings_result = mysqli_query($koneksi, $settings_query);
$settings = [];

while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['nama']] = $row['nilai'];
}

// Process return request
$pesan_kembali = '';
if (isset($_POST['kembali']) && isset($_POST['id_peminjaman'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];

    // Verify borrowing belongs to user
    $verify_query = "SELECT * FROM peminjaman WHERE id = $id_peminjaman AND id_pengguna = $id_pengguna";
    $verify_result = mysqli_query($koneksi, $verify_query);

    if (mysqli_num_rows($verify_result) == 1) {
        $peminjaman = mysqli_fetch_assoc($verify_result);
        $tanggal_kembali = date('Y-m-d');

        // Update borrowing status
        $update_query = "UPDATE peminjaman SET status = 'dikembalikan', tanggal_kembali = '$tanggal_kembali' 
                        WHERE id = $id_peminjaman";

        if (mysqli_query($koneksi, $update_query)) {
            // Check if return is late
            $tanggal_jatuh_tempo = new DateTime($peminjaman['tanggal_jatuh_tempo']);
            $kembali = new DateTime($tanggal_kembali);
            $hari_terlambat = $kembali->diff($tanggal_jatuh_tempo)->days;

            if ($kembali > $tanggal_jatuh_tempo) {
                // Calculate penalty (Rp 1000 per day)
                $denda_per_hari = isset($settings['denda_per_hari']) ? (int)$settings['denda_per_hari'] : 1000;
                $jumlah_denda = $hari_terlambat * $denda_per_hari;

                // Add penalty record
                $penalty_query = "INSERT INTO denda (id_peminjaman, id_pengguna, jumlah, status, tanggal_dibuat) 
                                VALUES ($id_peminjaman, $id_pengguna, $jumlah_denda, 'belum_dibayar', NOW())";
                mysqli_query($koneksi, $penalty_query);

                $pesan_kembali = '<div class="alert alert-warning">Buku dikembalikan terlambat ' . $hari_terlambat . ' hari. Denda sebesar Rp ' . number_format($jumlah_denda) . ' telah ditambahkan ke akun Anda.</div>';
            } else {
                $pesan_kembali = '<div class="alert alert-success">Buku berhasil dikembalikan.</div>';
            }

            // Refresh borrowed books
            $borrowed_result = mysqli_query($koneksi, $borrowed_query);
        } else {
            $pesan_kembali = '<div class="alert alert-error">Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.</div>';
        }
    }
}

// Process penalty payment
$pesan_pembayaran = '';
if (isset($_POST['bayar_denda']) && isset($_POST['id_denda'])) {
    $id_denda = (int)$_POST['id_denda'];

    // Verify penalty belongs to user
    $verify_query = "SELECT * FROM denda WHERE id = $id_denda AND id_pengguna = $id_pengguna";
    $verify_result = mysqli_query($koneksi, $verify_query);

    if (mysqli_num_rows($verify_result) == 1) {
        // Update penalty status
        $update_query = "UPDATE denda SET status = 'dibayar', tanggal_dibayar = NOW() WHERE id = $id_denda";

        if (mysqli_query($koneksi, $update_query)) {
            $pesan_pembayaran = '<div class="alert alert-success">Denda berhasil dibayar.</div>';

            // Refresh penalties
            $penalties_result = mysqli_query($koneksi, $penalties_query);
        } else {
            $pesan_pembayaran = '<div class="alert alert-error">Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.</div>';
        }
    }
}

// Process report lost book
$pesan_hilang = '';
if (isset($_POST['laporkan_hilang']) && isset($_POST['id_peminjaman'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];

    // Verify borrowing belongs to user
    $verify_query = "SELECT * FROM peminjaman WHERE id = $id_peminjaman AND id_pengguna = $id_pengguna";
    $verify_result = mysqli_query($koneksi, $verify_query);

    if (mysqli_num_rows($verify_result) == 1) {
        $tanggal_kembali = date('Y-m-d');

        // Update borrowing status
        $update_query = "UPDATE peminjaman SET status = 'hilang', tanggal_kembali = '$tanggal_kembali' 
                        WHERE id = $id_peminjaman";

        if (mysqli_query($koneksi, $update_query)) {
            // Reduce book stock
            $book_query = "SELECT id_buku FROM peminjaman WHERE id = $id_peminjaman";
            $book_result = mysqli_query($koneksi, $book_query);
            $book_data = mysqli_fetch_assoc($book_result);

            $update_stock_query = "UPDATE buku SET stok = stok - 1 WHERE id = {$book_data['id_buku']} AND stok > 0";
            mysqli_query($koneksi, $update_stock_query);

            // Add penalty for lost book
            $denda_buku_hilang = isset($settings['denda_buku_hilang']) ? (int)$settings['denda_buku_hilang'] : 50000;

            $penalty_query = "INSERT INTO denda (id_peminjaman, id_pengguna, jumlah, status, tanggal_dibuat) 
                            VALUES ($id_peminjaman, $id_pengguna, $denda_buku_hilang, 'belum_dibayar', NOW())";
            mysqli_query($koneksi, $penalty_query);

            $pesan_hilang = '<div class="alert alert-warning">Buku telah dilaporkan hilang. Denda sebesar Rp ' . number_format($denda_buku_hilang) . ' telah ditambahkan ke akun Anda.</div>';

            // Refresh borrowed books and penalties
            $borrowed_result = mysqli_query($koneksi, $borrowed_query);
            $penalties_result = mysqli_query($koneksi, $penalties_query);
        } else {
            $pesan_hilang = '<div class="alert alert-error">Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saya - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <h1>Dashboard Saya</h1>
            <p>Selamat datang, <?php echo htmlspecialchars($pengguna['nama']); ?></p>
        </div>

        <?php if (!empty($pesan_kembali)) echo $pesan_kembali; ?>
        <?php if (!empty($pesan_pembayaran)) echo $pesan_pembayaran; ?>
        <?php if (!empty($pesan_hilang)) echo $pesan_hilang; ?>

        <div class="dashboard-tabs">
            <a href="?tab=dipinjam" class="tab <?php echo $tab_aktif == 'dipinjam' ? 'active' : ''; ?>">
                Buku Dipinjam
            </a>
            <a href="?tab=riwayat" class="tab <?php echo $tab_aktif == 'riwayat' ? 'active' : ''; ?>">
                Riwayat Peminjaman
            </a>
            <a href="?tab=denda" class="tab <?php echo $tab_aktif == 'denda' ? 'active' : ''; ?>">
                Denda
            </a>
            <a href="?tab=profil" class="tab <?php echo $tab_aktif == 'profil' ? 'active' : ''; ?>">
                Profil Saya
            </a>
            <a href="?tab=info" class="tab <?php echo $tab_aktif == 'info' ? 'active' : ''; ?>">
                Informasi
            </a>
        </div>

        <div class="dashboard-content">
            <?php if ($tab_aktif == 'dipinjam'): ?>
                <div class="borrowed-books">
                    <h2>Buku yang Sedang Dipinjam</h2>

                    <?php if (mysqli_num_rows($borrowed_result) > 0): ?>
                        <div class="borrowed-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Buku</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($borrowed_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="book-info-mini">
                                                    <img src="<?php echo $row['sampul'] ? 'assets/images/buku/' . $row['sampul'] : 'assets/images/sampul-default.jpg'; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                                                        <p><?php echo htmlspecialchars($row['penulis']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                            <td class="<?php echo strtotime($row['tanggal_jatuh_tempo']) < time() ? 'overdue' : ''; ?>">
                                                <?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?>
                                                <?php if (strtotime($row['tanggal_jatuh_tempo']) < time()): ?>
                                                    <span class="overdue-label">Terlambat</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['status'] == 'dipinjam' ? 'Dipinjam' : 'Dikembalikan'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin melaporkan buku ini hilang? Anda akan dikenakan denda sebesar Rp <?php echo number_format(isset($settings['denda_buku_hilang']) ? $settings['denda_buku_hilang'] : 50000); ?>');">
                                                        <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman']; ?>">
                                                        <button type="submit" name="laporkan_hilang" class="btn btn-small btn-danger">Laporkan Hilang</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-results">Anda tidak memiliki buku yang sedang dipinjam.</div>
                    <?php endif; ?>
                </div>
            <?php elseif ($tab_aktif == 'riwayat'): ?>
                <div class="borrowing-history">
                    <h2>Riwayat Peminjaman</h2>

                    <div class="search-filters">
                        <form method="get" action="">
                            <input type="hidden" name="tab" value="riwayat">

                            <div class="form-group">
                                <label for="cari">Cari Buku</label>
                                <input type="text" id="cari" name="cari" placeholder="Judul atau penulis" value="<?php echo htmlspecialchars($cari); ?>">
                            </div>

                            <div class="form-group">
                                <label for="tanggal_mulai">Tanggal Mulai</label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $filter_tanggal_mulai; ?>">
                            </div>

                            <div class="form-group">
                                <label for="tanggal_akhir">Tanggal Akhir</label>
                                <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $filter_tanggal_akhir; ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Semua</option>
                                    <option value="dikembalikan" <?php echo $filter_status == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                                    <option value="hilang" <?php echo $filter_status == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <button style="border: none;">
                                    <a href="?tab=riwayat" class="btn btn-reset">Reset</a>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php if (mysqli_num_rows($history_result) > 0): ?>
                        <div class="history-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Buku</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="book-info-mini">
                                                    <img src="<?php echo $row['sampul'] ? 'assets/images/buku/' . $row['sampul'] : 'assets/images/sampul-default.jpg'; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                                                        <p><?php echo htmlspecialchars($row['penulis']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                                            <td><?php echo $row['tanggal_kembali'] ? date('d M Y', strtotime($row['tanggal_kembali'])) : '-'; ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'dikembalikan'): ?>
                                                    <span class="status-badge returned">Dikembalikan</span>
                                                <?php else: ?>
                                                    <span class="status-badge lost">Hilang</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-results">Anda tidak memiliki riwayat peminjaman.</div>
                    <?php endif; ?>
                </div>
            <?php elseif ($tab_aktif == 'denda'): ?>
                <div class="penalties">
                    <h2>Denda</h2>

                    <div class="search-filters">
                        <form method="get" action="">
                            <input type="hidden" name="tab" value="denda">

                            <div class="form-group">
                                <label for="cari">Cari Buku</label>
                                <input type="text" id="cari" name="cari" placeholder="Judul buku" value="<?php echo htmlspecialchars($cari); ?>">
                            </div>

                            <div class="form-group">
                                <label for="tanggal_mulai">Tanggal Mulai</label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $filter_tanggal_mulai; ?>">
                            </div>

                            <div class="form-group">
                                <label for="tanggal_akhir">Tanggal Akhir</label>
                                <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $filter_tanggal_akhir; ?>">
                            </div>

                            <div class="form-group">
                                <label for="status_denda">Status</label>
                                <select id="status_denda" name="status_denda">
                                    <option value="">Semua</option>
                                    <option value="belum_dibayar" <?php echo $filter_status_denda == 'belum_dibayar' ? 'selected' : ''; ?>>Belum Dibayar</option>
                                    <option value="dibayar" <?php echo $filter_status_denda == 'dibayar' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <button style="border: none;">
                                    <a href="?tab=denda" class="btn btn-reset">Reset</a>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php if (mysqli_num_rows($penalties_result) > 0): ?>
                        <div class="penalties-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Buku</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($penalties_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                                            <td>Rp <?php echo number_format($row['jumlah']); ?></td>
                                            <td><?php echo $row['status'] == 'belum_dibayar' ? 'Belum Dibayar' : 'Sudah Dibayar'; ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'belum_dibayar'): ?>
                                                    <form method="post">
                                                        <input type="hidden" name="id_denda" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="bayar_denda" class="btn btn-small">Bayar Sekarang</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="paid-label">Lunas</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-results">Anda tidak memiliki denda.</div>
                    <?php endif; ?>
                </div>
            <?php elseif ($tab_aktif == 'profil'): ?>
                <div class="user-profile">
                    <h2>Profil Saya</h2>

                    <div class="profile-info">
                        <div class="profile-field">
                            <label>Nama</label>
                            <p><?php echo htmlspecialchars($pengguna['nama']); ?></p>
                        </div>

                        <div class="profile-field">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($pengguna['email']); ?></p>
                        </div>

                        <div class="profile-field">
                            <label>Anggota Sejak</label>
                            <p><?php echo date('d M Y', strtotime($pengguna['tanggal_daftar'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab_aktif == 'info'): ?>
                <div class="info-section">
                    <h2>Informasi Peminjaman dan Denda</h2>

                    <div class="info-card">
                        <h3>Ketentuan Peminjaman</h3>
                        <ul>
                            <li>Durasi peminjaman buku adalah <strong><?php echo isset($settings['durasi_peminjaman']) ? $settings['durasi_peminjaman'] : 14; ?> hari</strong>.</li>
                            <li>Maksimal peminjaman buku adalah <strong><?php echo isset($settings['max_peminjaman']) ? $settings['max_peminjaman'] : 3; ?> buku</strong> secara bersamaan.</li>
                            <li>Buku harus dikembalikan langsung ke perpustakaan sebelum atau pada tanggal jatuh tempo.</li>
                            <li>Jika Anda tidak dapat mengembalikan buku tepat waktu, harap laporkan ke petugas perpustakaan.</li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h3>Ketentuan Denda</h3>
                        <ul>
                            <li>Keterlambatan pengembalian buku akan dikenakan denda sebesar <strong>Rp <?php echo number_format(isset($settings['denda_per_hari']) ? $settings['denda_per_hari'] : 1000); ?> per hari</strong>.</li>
                            <li>Buku yang hilang atau rusak parah akan dikenakan denda sebesar <strong>Rp <?php echo number_format(isset($settings['denda_buku_hilang']) ? $settings['denda_buku_hilang'] : 50000); ?></strong>.</li>
                            <li>Denda harus dibayar sebelum Anda dapat meminjam buku lagi.</li>
                            <li>Pembayaran denda dapat dilakukan di perpustakaan atau melalui sistem online.</li>
                        </ul>
                    </div>

                    <div class="info-card">
                        <h3>Cara Melaporkan Buku Hilang</h3>
                        <ol>
                            <li>Buka tab "Buku Dipinjam" di dashboard Anda.</li>
                            <li>Klik tombol "Laporkan Hilang" pada buku yang hilang.</li>
                            <li>Konfirmasi laporan Anda.</li>
                            <li>Denda buku hilang akan ditambahkan ke akun Anda.</li>
                            <li>Bayar denda melalui tab "Denda".</li>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <style>
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-badge.returned {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .status-badge.lost {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .info-card {
            background-color: var(--bg-light);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .info-card ul,
        .info-card ol {
            padding-left: 20px;
        }

        .info-card li {
            margin-bottom: 10px;
        }

        .search-filters {
            background-color: white;
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .search-filters form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .search-filters .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        .search-filters .form-group label {
            margin-bottom: 5px;
            display: block;
        }

        .search-filters .form-actions {
            margin-top: 0;
        }

        .search-filters .btn {
            margin-bottom: 0;
        }

        .search-filters .btn-reset {
            background-color: #95a5a6;
            color: white;
        }

        .search-filters .btn-reset:hover {
            background-color: #7f8c8d;
        }

        @media (min-width: 768px) {
            .info-section {
                grid-template-columns: repeat(2, 1fr);
            }

            .info-card:first-child {
                grid-column: 1 / -1;
            }
        }
    </style>
</body>

</html>