<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$pesan = '';

// Search and filter parameters
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$filter_tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$filter_anggota = isset($_GET['id_anggota']) ? (int)$_GET['id_anggota'] : 0;
$filter_min_jumlah = isset($_GET['min_jumlah']) ? (int)$_GET['min_jumlah'] : 0;
$filter_max_jumlah = isset($_GET['max_jumlah']) ? (int)$_GET['max_jumlah'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['bayar_denda'])) {
        $id_denda = (int)$_POST['id_denda'];

        // Update penalty status
        $query = "UPDATE denda SET status = 'dibayar', tanggal_dibayar = NOW() WHERE id = $id_denda";

        if (mysqli_query($koneksi, $query)) {
            $pesan = '<div class="alert alert-success">Denda berhasil diperbarui menjadi dibayar.</div>';
        } else {
            $pesan = '<div class="alert alert-error">Error memperbarui status denda: ' . mysqli_error($koneksi) . '</div>';
        }
    }
}

// Get users for dropdown
$users_query = "SELECT id, nama, email FROM pengguna WHERE peran = 'anggota' ORDER BY nama ASC";
$users_result = mysqli_query($koneksi, $users_query);

// Build query for penalties with search and filters
$penalties_query = "SELECT d.*, p.id as id_peminjaman, b.judul as judul_buku, u.nama as nama_pengguna, u.email as email_pengguna 
                   FROM denda d 
                   JOIN peminjaman p ON d.id_peminjaman = p.id 
                   JOIN buku b ON p.id_buku = b.id 
                   JOIN pengguna u ON d.id_pengguna = u.id 
                   WHERE 1=1";

if (!empty($cari)) {
    $penalties_query .= " AND (b.judul LIKE '%$cari%' OR u.nama LIKE '%$cari%' OR u.email LIKE '%$cari%')";
}

if ($filter_anggota > 0) {
    $penalties_query .= " AND d.id_pengguna = $filter_anggota";
}

if (!empty($filter_tanggal_mulai)) {
    $penalties_query .= " AND d.tanggal_dibuat >= '$filter_tanggal_mulai'";
}

if (!empty($filter_tanggal_akhir)) {
    $penalties_query .= " AND d.tanggal_dibuat <= '$filter_tanggal_akhir'";
}

if ($filter_min_jumlah > 0) {
    $penalties_query .= " AND d.jumlah >= $filter_min_jumlah";
}

if ($filter_max_jumlah > 0) {
    $penalties_query .= " AND d.jumlah <= $filter_max_jumlah";
}

if ($filter == 'belum_dibayar') {
    $penalties_query .= " AND d.status = 'belum_dibayar'";
} else if ($filter == 'dibayar') {
    $penalties_query .= " AND d.status = 'dibayar'";
}

$penalties_query .= " ORDER BY d.tanggal_dibuat DESC";
$penalties_result = mysqli_query($koneksi, $penalties_query);

// Get total penalties
$total_query = "SELECT SUM(jumlah) as total FROM denda";
$total_result = mysqli_query($koneksi, $total_query);
$total_denda = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// Get total unpaid penalties
$unpaid_query = "SELECT SUM(jumlah) as total FROM denda WHERE status = 'belum_dibayar'";
$unpaid_result = mysqli_query($koneksi, $unpaid_query);
$total_belum_dibayar = mysqli_fetch_assoc($unpaid_result)['total'] ?? 0;

// Get total paid penalties
$paid_query = "SELECT SUM(jumlah) as total FROM denda WHERE status = 'dibayar'";
$paid_result = mysqli_query($koneksi, $paid_query);
$total_dibayar = mysqli_fetch_assoc($paid_result)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Denda - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Kelola Denda</h1>
            </div>

            <?php echo $pesan; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(74, 111, 165, 0.1);">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
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
                    <div class="stat-icon" style="background-color: rgba(231, 76, 60, 0.1);">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #e74c3c;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>Belum Dibayar</h3>
                        <p class="stat-number">Rp <?php echo number_format($total_belum_dibayar); ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(39, 174, 96, 0.1);">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="color: #27ae60;">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>Sudah Dibayar</h3>
                        <p class="stat-number">Rp <?php echo number_format($total_dibayar); ?></p>
                    </div>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?filter=semua<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?><?php echo $filter_min_jumlah > 0 ? '&min_jumlah=' . $filter_min_jumlah : ''; ?><?php echo $filter_max_jumlah > 0 ? '&max_jumlah=' . $filter_max_jumlah : ''; ?>" class="tab <?php echo $filter == 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="?filter=belum_dibayar<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?><?php echo $filter_min_jumlah > 0 ? '&min_jumlah=' . $filter_min_jumlah : ''; ?><?php echo $filter_max_jumlah > 0 ? '&max_jumlah=' . $filter_max_jumlah : ''; ?>" class="tab <?php echo $filter == 'belum_dibayar' ? 'active' : ''; ?>">Belum Dibayar</a>
                <a href="?filter=dibayar<?php echo !empty($cari) ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filter_anggota > 0 ? '&id_anggota=' . $filter_anggota : ''; ?><?php echo !empty($filter_tanggal_mulai) ? '&tanggal_mulai=' . $filter_tanggal_mulai : ''; ?><?php echo !empty($filter_tanggal_akhir) ? '&tanggal_akhir=' . $filter_tanggal_akhir : ''; ?><?php echo $filter_min_jumlah > 0 ? '&min_jumlah=' . $filter_min_jumlah : ''; ?><?php echo $filter_max_jumlah > 0 ? '&max_jumlah=' . $filter_max_jumlah : ''; ?>" class="tab <?php echo $filter == 'dibayar' ? 'active' : ''; ?>">Sudah Dibayar</a>
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

                    <div class="form-group">
                        <label for="min_jumlah">Jumlah Minimal (Rp)</label>
                        <input type="number" id="min_jumlah" name="min_jumlah" placeholder="Minimal" value="<?php echo $filter_min_jumlah > 0 ? $filter_min_jumlah : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="max_jumlah">Jumlah Maksimal (Rp)</label>
                        <input type="number" id="max_jumlah" name="max_jumlah" placeholder="Maksimal" value="<?php echo $filter_max_jumlah > 0 ? $filter_max_jumlah : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="denda.php?filter=<?php echo $filter; ?>" class="btn btn-reset">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Jumlah Denda</th>
                            <th>Tanggal Dibuat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($penalties_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($penalties_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nama_pengguna']); ?></td>
                                    <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                    <td>Rp <?php echo number_format($row['jumlah']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['tanggal_dibuat'])); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'belum_dibayar'): ?>
                                            <span class="status-badge unpaid">Belum Dibayar</span>
                                        <?php else: ?>
                                            <span class="status-badge paid">Dibayar</span>
                                            <div class="small-text">
                                                <?php echo date('d M Y', strtotime($row['tanggal_dibayar'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <?php if ($row['status'] == 'belum_dibayar'): ?>
                                            <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menandai denda ini sebagai dibayar?');">
                                                <input type="hidden" name="id_denda" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="bayar_denda" class="btn btn-small">Tandai Dibayar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="paid-label">Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Tidak ada data denda yang ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-badge.unpaid {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .status-badge.paid {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .small-text {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 3px;
        }

        .paid-label {
            display: inline-block;
            padding: 3px 8px;
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border-radius: 12px;
            font-size: 0.8rem;
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

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
        }
    </style>
</body>

</html>