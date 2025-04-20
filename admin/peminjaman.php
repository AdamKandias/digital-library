<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'daftar';
$id_peminjaman = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$pesan = '';

// Search and filter parameters
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_anggota = isset($_GET['id_anggota']) ? (int)$_GET['id_anggota'] : 0;

// Get settings for borrowing duration
$settings_query = "SELECT * FROM pengaturan WHERE nama = 'durasi_peminjaman'";
$settings_result = mysqli_query($koneksi, $settings_query);
$durasi_peminjaman = 14; // Default 14 days

if (mysqli_num_rows($settings_result) > 0) {
    $setting = mysqli_fetch_assoc($settings_result);
    $durasi_peminjaman = (int)$setting['nilai'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_peminjaman'])) {
        $id_pengguna = (int)$_POST['id_pengguna'];
        $id_buku = (int)$_POST['id_buku'];
        $tanggal_pinjam = $_POST['tanggal_pinjam'];
        $tanggal_kembali = date('Y-m-d', strtotime($tanggal_pinjam . ' + ' . $durasi_peminjaman . ' days'));

        // Check if book is available
        $check_query = "SELECT stok FROM buku WHERE id = $id_buku";
        $check_result = mysqli_query($koneksi, $check_query);
        $buku = mysqli_fetch_assoc($check_result);

        $borrowed_query = "SELECT COUNT(*) as dipinjam FROM peminjaman WHERE id_buku = $id_buku AND status = 'dipinjam'";
        $borrowed_result = mysqli_query($koneksi, $borrowed_query);
        $borrowed = mysqli_fetch_assoc($borrowed_result);

        $stok_tersedia = $buku['stok'] - $borrowed['dipinjam'];

        if ($stok_tersedia <= 0) {
            $pesan = '<div class="alert alert-error">Buku tidak tersedia untuk dipinjam.</div>';
        } else {
            // Check if user has any penalties
            $penalty_query = "SELECT * FROM denda WHERE id_pengguna = $id_pengguna AND status = 'belum_dibayar'";
            $penalty_result = mysqli_query($koneksi, $penalty_query);

            if (mysqli_num_rows($penalty_result) > 0) {
                $pesan = '<div class="alert alert-error">Anggota memiliki denda yang belum dibayar.</div>';
            } else {
                // Check how many books the user has borrowed
                $active_borrows_query = "SELECT COUNT(*) as jumlah FROM peminjaman WHERE id_pengguna = $id_pengguna AND status = 'dipinjam'";
                $active_borrows_result = mysqli_query($koneksi, $active_borrows_query);
                $active_borrows = mysqli_fetch_assoc($active_borrows_result);

                if ($active_borrows['jumlah'] >= 3) {
                    $pesan = '<div class="alert alert-error">Anggota sudah meminjam 3 buku (batas maksimum).</div>';
                } else {
                    // Add new borrowing
                    $query = "INSERT INTO peminjaman (id_pengguna, id_buku, tanggal_pinjam, tanggal_jatuh_tempo, status) 
                             VALUES ($id_pengguna, $id_buku, '$tanggal_pinjam', '$tanggal_kembali', 'dipinjam')";

                    if (mysqli_query($koneksi, $query)) {
                        $pesan = '<div class="alert alert-success">Peminjaman berhasil ditambahkan.</div>';
                        $aksi = 'daftar';
                    } else {
                        $pesan = '<div class="alert alert-error">Error menambahkan peminjaman: ' . mysqli_error($koneksi) . '</div>';
                    }
                }
            }
        }
    } else if (isset($_POST['update_status'])) {
        $id = (int)$_POST['id_peminjaman'];
        $status_lama = mysqli_real_escape_string($koneksi, $_POST['status_lama']);
        $status_baru = mysqli_real_escape_string($koneksi, $_POST['status']);
        $tanggal_kembali = ($status_baru == 'dikembalikan' || $status_baru == 'hilang') ? date('Y-m-d') : NULL;

        // Get book ID
        $borrow_query = "SELECT id_pengguna, id_buku FROM peminjaman WHERE id = $id";
        $borrow_result = mysqli_query($koneksi, $borrow_query);
        $borrow_data = mysqli_fetch_assoc($borrow_result);
        $id_buku = $borrow_data['id_buku'];
        $id_pengguna = $borrow_data['id_pengguna'];

        // Update borrowing status
        if ($tanggal_kembali) {
            $query = "UPDATE peminjaman SET status = '$status_baru', tanggal_kembali = '$tanggal_kembali' WHERE id = $id";
        } else {
            $query = "UPDATE peminjaman SET status = '$status_baru' WHERE id = $id";
        }

        if (mysqli_query($koneksi, $query)) {
            // Handle status changes
            if ($status_lama == 'dipinjam' && $status_baru == 'dikembalikan') {
                // If returned, check if late and add penalty if needed
                $borrow_query = "SELECT tanggal_jatuh_tempo FROM peminjaman WHERE id = $id";
                $borrow_result = mysqli_query($koneksi, $borrow_query);
                $peminjaman = mysqli_fetch_assoc($borrow_result);

                $tanggal_jatuh_tempo = new DateTime($peminjaman['tanggal_jatuh_tempo']);
                $kembali = new DateTime($tanggal_kembali);

                if ($kembali > $tanggal_jatuh_tempo) {
                    $hari_terlambat = $kembali->diff($tanggal_jatuh_tempo)->days;

                    // Get denda_per_hari setting
                    $denda_query = "SELECT nilai FROM pengaturan WHERE nama = 'denda_per_hari'";
                    $denda_result = mysqli_query($koneksi, $denda_query);
                    $denda_per_hari = 1000; // Default

                    if (mysqli_num_rows($denda_result) > 0) {
                        $denda_row = mysqli_fetch_assoc($denda_result);
                        $denda_per_hari = (int)$denda_row['nilai'];
                    }

                    // Calculate penalty
                    $jumlah_denda = $hari_terlambat * $denda_per_hari;

                    // Add penalty record
                    $penalty_query = "INSERT INTO denda (id_peminjaman, id_pengguna, jumlah, status, tanggal_dibuat) 
                                    VALUES ($id, $id_pengguna, $jumlah_denda, 'belum_dibayar', NOW())";
                    mysqli_query($koneksi, $penalty_query);

                    $pesan = '<div class="alert alert-success">Status peminjaman diperbarui. Denda sebesar Rp ' . number_format($jumlah_denda) . ' telah ditambahkan karena terlambat ' . $hari_terlambat . ' hari.</div>';
                } else {
                    $pesan = '<div class="alert alert-success">Status peminjaman diperbarui.</div>';
                }
            } else if ($status_lama == 'dipinjam' && $status_baru == 'hilang') {
                // Reduce book stock when book is reported lost
                $update_stock_query = "UPDATE buku SET stok = stok - 1 WHERE id = $id_buku AND stok > 0";
                mysqli_query($koneksi, $update_stock_query);

                // Get denda_buku_hilang setting
                $denda_query = "SELECT nilai FROM pengaturan WHERE nama = 'denda_buku_hilang'";
                $denda_result = mysqli_query($koneksi, $denda_query);
                $denda_buku_hilang = 50000; // Default

                if (mysqli_num_rows($denda_result) > 0) {
                    $denda_row = mysqli_fetch_assoc($denda_result);
                    $denda_buku_hilang = (int)$denda_row['nilai'];
                }

                // Add penalty record for lost book
                $penalty_query = "INSERT INTO denda (id_peminjaman, id_pengguna, jumlah, status, tanggal_dibuat) 
                                VALUES ($id, $id_pengguna, $denda_buku_hilang, 'belum_dibayar', NOW())";
                mysqli_query($koneksi, $penalty_query);

                $pesan = '<div class="alert alert-success">Status peminjaman diperbarui. Denda sebesar Rp ' . number_format($denda_buku_hilang) . ' telah ditambahkan untuk buku yang hilang.</div>';
            } else if ($status_lama == 'hilang' && $status_baru == 'dikembalikan') {
                // Increase book stock when status changed from lost to returned
                $update_stock_query = "UPDATE buku SET stok = stok + 1 WHERE id = $id_buku";
                mysqli_query($koneksi, $update_stock_query);

                // Delete lost book penalty
                $delete_penalty_query = "DELETE FROM denda WHERE id_peminjaman = $id AND status = 'belum_dibayar'";
                mysqli_query($koneksi, $delete_penalty_query);

                $pesan = '<div class="alert alert-success">Status peminjaman diperbarui. Buku telah dikembalikan dan denda buku hilang telah dihapus.</div>';
            } else if ($status_lama == 'hilang' && $status_baru == 'dipinjam') {
                // Increase book stock when status changed from lost to returned
                $update_stock_query = "UPDATE buku SET stok = stok + 1 WHERE id = $id_buku";
                mysqli_query($koneksi, $update_stock_query);

                // Delete penalty
                $delete_penalty_query = "DELETE FROM denda WHERE id_peminjaman = $id AND status = 'belum_dibayar'";
                mysqli_query($koneksi, $delete_penalty_query);

                $pesan = '<div class="alert alert-success">Status peminjaman diperbarui. Denda buku hilang telah dihapus karena status diubah menjadi dipinjam kembali.</div>';
            } else {
                $pesan = '<div class="alert alert-success">Status peminjaman diperbarui.</div>';
            }
        } else {
            $pesan = '<div class="alert alert-error">Error memperbarui status peminjaman: ' . mysqli_error($koneksi) . '</div>';
        }

        $aksi = 'daftar';
    }
}

// Get borrowing data for edit form
$peminjaman = null;
if ($aksi == 'edit' && $id_peminjaman > 0) {
    $query = "SELECT p.*, b.judul as judul_buku, u.nama as nama_pengguna 
              FROM peminjaman p 
              JOIN buku b ON p.id_buku = b.id 
              JOIN pengguna u ON p.id_pengguna = u.id 
              WHERE p.id = $id_peminjaman";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $peminjaman = mysqli_fetch_assoc($result);
    } else {
        $aksi = 'daftar';
    }
}

// Get users for dropdown
$users_query = "SELECT id, nama, email FROM pengguna WHERE peran = 'anggota' ORDER BY nama ASC";
$users_result = mysqli_query($koneksi, $users_query);

// Get books for dropdown
$books_query = "SELECT id, judul, penulis FROM buku ORDER BY judul ASC";
$books_result = mysqli_query($koneksi, $books_query);

// Build query for borrowings list with search and filters
$borrowings_query = "SELECT p.*, b.judul as judul_buku, b.penulis, u.nama as nama_pengguna, u.email as email_pengguna 
                    FROM peminjaman p 
                    JOIN buku b ON p.id_buku = b.id 
                    JOIN pengguna u ON p.id_pengguna = u.id 
                    WHERE 1=1";

if (!empty($cari)) {
    $borrowings_query .= " AND (b.judul LIKE '%$cari%' OR u.nama LIKE '%$cari%' OR u.email LIKE '%$cari%')";
}

if ($filter_anggota > 0) {
    $borrowings_query .= " AND p.id_pengguna = $filter_anggota";
}

if (!empty($filter_tanggal_mulai)) {
    $borrowings_query .= " AND p.tanggal_pinjam >= '$filter_tanggal_mulai'";
}

if (!empty($filter_tanggal_akhir)) {
    $borrowings_query .= " AND p.tanggal_pinjam <= '$filter_tanggal_akhir'";
}

if ($filter == 'dipinjam') {
    $borrowings_query .= " AND p.status = 'dipinjam'";
} else if ($filter == 'dikembalikan') {
    $borrowings_query .= " AND p.status = 'dikembalikan'";
} else if ($filter == 'hilang') {
    $borrowings_query .= " AND p.status = 'hilang'";
} else if ($filter == 'terlambat') {
    $borrowings_query .= " AND p.status = 'dipinjam' AND p.tanggal_jatuh_tempo < CURDATE()";
}

$borrowings_query .= " ORDER BY p.tanggal_pinjam DESC";
$borrowings_result = mysqli_query($koneksi, $borrowings_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $aksi == 'tambah' ? 'Tambah Peminjaman Baru' : ($aksi == 'edit' ? 'Edit Peminjaman' : 'Kelola Peminjaman'); ?></h1>
                <?php if ($aksi == 'daftar'): ?>
                    <a href="?aksi=tambah" class="btn btn-primary">Tambah Peminjaman Baru</a>
                <?php endif; ?>
            </div>

            <?php echo $pesan; ?>

            <?php if ($aksi == 'daftar'): ?>
                <div class="filter-tabs">
                    <a href="?filter=semua<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?>" class="tab <?php echo $filter == 'semua' ? 'active' : ''; ?>">Semua</a>
                    <a href="?filter=dipinjam<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?>" class="tab <?php echo $filter == 'dipinjam' ? 'active' : ''; ?>">Dipinjam</a>
                    <a href="?filter=dikembalikan<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?>" class="tab <?php echo $filter == 'dikembalikan' ? 'active' : ''; ?>">Dikembalikan</a>
                    <a href="?filter=hilang<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?>" class="tab <?php echo $filter == 'hilang' ? 'active' : ''; ?>">Hilang</a>
                    <a href="?filter=terlambat<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?>" class="tab <?php echo $filter == 'terlambat' ? 'active' : ''; ?>">Terlambat</a>
                </div>

                <div class="search-filters">
                    <form method="get" action="">
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">

                        <div class="form-group">
                            <label for="cari">Cari</label>
                            <input type="text" id="cari" name="cari" placeholder="Judul buku atau nama anggota" value="<?php echo htmlspecialchars($cari); ?>">
                        </div>

                        <div class="form-group">
                            <label for="id_anggota">Anggota</label>
                            <select id="id_anggota" name="id_anggota">
                                <option value="0">Semua Anggota</option>
                                <?php
                                mysqli_data_seek($users_result, 0);
                                while ($user = mysqli_fetch_assoc($users_result)) {
                                    $selected = ($filter_anggota == $user['id']) ? 'selected' : '';
                                    echo '<option value="' . $user['id'] . '" ' . $selected . '>' . htmlspecialchars($user['nama']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $filter_tanggal_mulai; ?>">
                        </div>

                        <div class="form-group">
                            <label for="tanggal_akhir">Tanggal Akhir</label>
                            <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $filter_tanggal_akhir; ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="peminjaman.php?filter=<?php echo $filter; ?>" class="btn btn-reset">Reset</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($aksi == 'tambah'): ?>
                <div class="admin-form-container">
                    <form method="post" class="admin-form">
                        <div class="form-group">
                            <label for="id_pengguna">Anggota</label>
                            <select id="id_pengguna" name="id_pengguna" required>
                                <option value="">Pilih Anggota</option>
                                <?php
                                mysqli_data_seek($users_result, 0);
                                while ($user = mysqli_fetch_assoc($users_result)) {
                                    echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['nama']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="id_buku">Buku</label>
                            <select id="id_buku" name="id_buku" required>
                                <option value="">Pilih Buku</option>
                                <?php
                                mysqli_data_seek($books_result, 0);
                                while ($book = mysqli_fetch_assoc($books_result)) {
                                    echo '<option value="' . $book['id'] . '">' . htmlspecialchars($book['judul']) . ' - ' . htmlspecialchars($book['penulis']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_pinjam">Tanggal Pinjam</label>
                            <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="tambah_peminjaman" class="btn btn-primary">Tambah Peminjaman</button>
                            <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($aksi == 'edit'): ?>
                <div class="admin-form-container">
                    <div class="borrowing-details">
                        <h2>Detail Peminjaman</h2>

                        <div class="detail-row">
                            <div class="detail-label">Anggota:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($peminjaman['nama_pengguna']); ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">Buku:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($peminjaman['judul_buku']); ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">Tanggal Pinjam:</div>
                            <div class="detail-value"><?php echo date('d M Y', strtotime($peminjaman['tanggal_pinjam'])); ?></div>
                        </div>

                        <div class="detail-row">
                            <div class="detail-label">Tanggal Jatuh Tempo:</div>
                            <div class="detail-value"><?php echo date('d M Y', strtotime($peminjaman['tanggal_jatuh_tempo'])); ?></div>
                        </div>

                        <?php if ($peminjaman['tanggal_kembali']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Tanggal Kembali:</div>
                                <div class="detail-value"><?php echo date('d M Y', strtotime($peminjaman['tanggal_kembali'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value"><?php echo ucfirst($peminjaman['status']); ?></div>
                        </div>
                    </div>

                    <form method="post" class="admin-form">
                        <input type="hidden" name="id_peminjaman" value="<?php echo $peminjaman['id']; ?>">
                        <input type="hidden" name="status_lama" value="<?php echo $peminjaman['status']; ?>">

                        <div class="form-group">
                            <label for="status">Perbarui Status</label>
                            <select id="status" name="status" required>
                                <option value="dipinjam" <?php echo $peminjaman['status'] == 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                                <option value="dikembalikan" <?php echo $peminjaman['status'] == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                                <option value="hilang" <?php echo $peminjaman['status'] == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_status" class="btn btn-primary">Perbarui Status</button>
                            <a href="peminjaman.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tanggal Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($borrowings_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($borrowings_result)): ?>
                                    <tr class="<?php echo ($row['status'] == 'dipinjam' && strtotime($row['tanggal_jatuh_tempo']) < time()) ? 'overdue' : ''; ?>">
                                        <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                        <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
                                        <td>
                                            <?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?>
                                            <?php if ($row['status'] == 'dipinjam' && strtotime($row['tanggal_jatuh_tempo']) < time()): ?>
                                                <span class="overdue-label">Terlambat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'dipinjam'): ?>
                                                <span class="status-badge borrowed">Dipinjam</span>
                                            <?php elseif ($row['status'] == 'dikembalikan'): ?>
                                                <span class="status-badge returned">Dikembalikan</span>
                                            <?php else: ?>
                                                <span class="status-badge lost">Hilang</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="?aksi=edit&id=<?php echo $row['id']; ?>" class="btn btn-small">Edit</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Tidak ada data peminjaman yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .filter-tabs {
            display: flex;
            margin-bottom: 20px;
            background-color: white;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .filter-tabs .tab {
            padding: 10px 20px;
            color: var(--text-color);
            text-decoration: none;
        }

        .filter-tabs .tab.active {
            background-color: var(--primary-color);
            color: white;
        }

        .borrowing-details {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            width: 150px;
            font-weight: 500;
        }

        tr.overdue {
            background-color: rgba(231, 76, 60, 0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-badge.borrowed {
            background-color: rgba(74, 111, 165, 0.1);
            color: var(--primary-color);
        }

        .status-badge.returned {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .status-badge.lost {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
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
    </style>
</body>

</html>