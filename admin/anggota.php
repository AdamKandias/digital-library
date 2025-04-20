<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'daftar';
$id_pengguna = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = '';

// Search and filter parameters
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_peran = isset($_GET['peran']) ? mysqli_real_escape_string($koneksi, $_GET['peran']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_anggota']) || isset($_POST['update_anggota'])) {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $peran = mysqli_real_escape_string($koneksi, $_POST['peran']);

        // For new member or password change
        $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : '';

        if (isset($_POST['tambah_anggota'])) {
            // Check if email already exists
            $check_query = "SELECT * FROM pengguna WHERE email = '$email'";
            $check_result = mysqli_query($koneksi, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $pesan = '<div class="alert alert-error">Email sudah terdaftar.</div>';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Add new member
                $query = "INSERT INTO pengguna (nama, email, password, peran, tanggal_daftar) 
                         VALUES ('$nama', '$email', '$hashed_password', '$peran', NOW())";

                if (mysqli_query($koneksi, $query)) {
                    $pesan = '<div class="alert alert-success">Anggota berhasil ditambahkan.</div>';
                    $aksi = 'daftar';
                } else {
                    $pesan = '<div class="alert alert-error">Error menambahkan anggota: ' . mysqli_error($koneksi) . '</div>';
                }
            }
        } else if (isset($_POST['update_anggota'])) {
            $id = (int)$_POST['id_pengguna'];

            // Check if email already exists (except for this user)
            $check_query = "SELECT * FROM pengguna WHERE email = '$email' AND id != $id";
            $check_result = mysqli_query($koneksi, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $pesan = '<div class="alert alert-error">Email sudah digunakan oleh anggota lain.</div>';
            } else {
                // Update member
                if (!empty($password)) {
                    // Update with new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE pengguna SET nama = '$nama', email = '$email', password = '$hashed_password', peran = '$peran' WHERE id = $id";
                } else {
                    // Update without changing password
                    $query = "UPDATE pengguna SET nama = '$nama', email = '$email', peran = '$peran' WHERE id = $id";
                }

                if (mysqli_query($koneksi, $query)) {
                    $pesan = '<div class="alert alert-success">Anggota berhasil diperbarui.</div>';
                    $aksi = 'daftar';
                } else {
                    $pesan = '<div class="alert alert-error">Error memperbarui anggota: ' . mysqli_error($koneksi) . '</div>';
                }
            }
        }
    } else if (isset($_POST['hapus_anggota'])) {
        $id = (int)$_POST['id_pengguna'];

        // Check if member has active borrowings
        $check_query = "SELECT COUNT(*) as jumlah FROM peminjaman WHERE id_pengguna = $id AND status = 'dipinjam'";
        $check_result = mysqli_query($koneksi, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);

        if ($check_row['jumlah'] > 0) {
            $pesan = '<div class="alert alert-error">Tidak dapat menghapus anggota karena masih memiliki ' . $check_row['jumlah'] . ' peminjaman aktif.</div>';
        } else {
            // Check if member has unpaid penalties
            $penalty_query = "SELECT COUNT(*) as jumlah FROM denda WHERE id_pengguna = $id AND status = 'belum_dibayar'";
            $penalty_result = mysqli_query($koneksi, $penalty_query);
            $penalty_row = mysqli_fetch_assoc($penalty_result);

            if ($penalty_row['jumlah'] > 0) {
                $pesan = '<div class="alert alert-error">Tidak dapat menghapus anggota karena masih memiliki ' . $penalty_row['jumlah'] . ' denda yang belum dibayar.</div>';
            } else {
                // Delete member
                $query = "DELETE FROM pengguna WHERE id = $id";

                if (mysqli_query($koneksi, $query)) {
                    $pesan = '<div class="alert alert-success">Anggota berhasil dihapus.</div>';
                } else {
                    $pesan = '<div class="alert alert-error">Error menghapus anggota: ' . mysqli_error($koneksi) . '</div>';
                }
            }
        }

        $aksi = 'daftar';
    }
}

// Get member data for edit form
$pengguna = null;
if ($aksi == 'edit' && $id_pengguna > 0) {
    $query = "SELECT * FROM pengguna WHERE id = $id_pengguna";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $pengguna = mysqli_fetch_assoc($result);
    } else {
        $aksi = 'daftar';
    }
}

// Get members for list view with search and filter
$members_query = "SELECT * FROM pengguna WHERE 1=1";

if (!empty($cari)) {
    $members_query .= " AND (nama LIKE '%$cari%' OR email LIKE '%$cari%')";
}

if (!empty($filter_peran)) {
    $members_query .= " AND peran = '$filter_peran'";
}

$members_query .= " ORDER BY nama ASC";
$members_result = mysqli_query($koneksi, $members_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anggota - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $aksi == 'tambah' ? 'Tambah Anggota Baru' : ($aksi == 'edit' ? 'Edit Anggota' : 'Kelola Anggota'); ?></h1>
                <?php if ($aksi == 'daftar'): ?>
                    <a href="?aksi=tambah" class="btn btn-primary">Tambah Anggota Baru</a>
                <?php endif; ?>
            </div>

            <?php echo $pesan; ?>

            <?php if ($aksi == 'tambah' || $aksi == 'edit'): ?>
                <div class="admin-form-container">
                    <form method="post" class="admin-form">
                        <?php if ($aksi == 'edit'): ?>
                            <input type="hidden" name="id_pengguna" value="<?php echo $pengguna['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" value="<?php echo $aksi == 'edit' ? htmlspecialchars($pengguna['nama']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $aksi == 'edit' ? htmlspecialchars($pengguna['email']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" <?php echo $aksi == 'tambah' ? 'required' : ''; ?>>
                            <?php if ($aksi == 'edit'): ?>
                                <p class="form-help">Biarkan kosong untuk mempertahankan password saat ini</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="peran">Peran</label>
                            <select id="peran" name="peran" required>
                                <option value="anggota" <?php echo ($aksi == 'edit' && $pengguna['peran'] == 'anggota') ? 'selected' : ''; ?>>Anggota</option>
                                <option value="admin" <?php echo ($aksi == 'edit' && $pengguna['peran'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="<?php echo $aksi == 'tambah' ? 'tambah_anggota' : 'update_anggota'; ?>" class="btn btn-primary">
                                <?php echo $aksi == 'tambah' ? 'Tambah Anggota' : 'Perbarui Anggota'; ?>
                            </button>
                            <a href="anggota.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="search-filters">
                    <form method="get" action="">
                        <div class="form-group">
                            <label for="cari">Cari Anggota</label>
                            <input type="text" id="cari" name="cari" placeholder="Nama atau email" value="<?php echo htmlspecialchars($cari); ?>">
                        </div>

                        <div class="form-group">
                            <label for="peran">Peran</label>
                            <select id="peran" name="peran">
                                <option value="">Semua</option>
                                <option value="anggota" <?php echo $filter_peran == 'anggota' ? 'selected' : ''; ?>>Anggota</option>
                                <option value="admin" <?php echo $filter_peran == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Cari</button>
                            <a href="anggota.php" class="btn btn-reset">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($members_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($members_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo $row['peran'] == 'admin' ? 'Admin' : 'Anggota'; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_daftar'])); ?></td>
                                        <td class="actions">
                                            <a href="?aksi=edit&id=<?php echo $row['id']; ?>" class="btn btn-small">Edit</a>
                                            <?php if ($row['id'] != $_SESSION['id_pengguna']): // Prevent deleting self 
                                            ?>
                                                <form method="post" class="delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus anggota ini?');">
                                                    <input type="hidden" name="id_pengguna" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="hapus_anggota" class="btn btn-small btn-danger">Hapus</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Tidak ada anggota yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
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