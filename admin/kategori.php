<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'daftar';
$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = '';

// Search parameter
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_kategori']) || isset($_POST['update_kategori'])) {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);

        // Handle file upload for icon
        $ikon = '';
        if (isset($_FILES['ikon']) && $_FILES['ikon']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['ikon']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = 'kategori_' . uniqid() . '.' . $ext;
                $upload_dir = '../assets/images/kategori/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['ikon']['tmp_name'], $upload_dir . $new_filename)) {
                    $ikon = $new_filename;
                }
            }
        }

        if (isset($_POST['tambah_kategori'])) {
            // Add new category
            $query = "INSERT INTO kategori (nama, ikon, tanggal_dibuat) VALUES ('$nama', '$ikon', NOW())";

            if (mysqli_query($koneksi, $query)) {
                $pesan = '<div class="alert alert-success">Kategori berhasil ditambahkan.</div>';
                $aksi = 'daftar';
            } else {
                $pesan = '<div class="alert alert-error">Error menambahkan kategori: ' . mysqli_error($koneksi) . '</div>';
            }
        } else if (isset($_POST['update_kategori'])) {
            // Update existing category
            $id = (int)$_POST['id_kategori'];

            if ($ikon) {
                $query = "UPDATE kategori SET nama = '$nama', ikon = '$ikon' WHERE id = $id";
            } else {
                $query = "UPDATE kategori SET nama = '$nama' WHERE id = $id";
            }

            if (mysqli_query($koneksi, $query)) {
                $pesan = '<div class="alert alert-success">Kategori berhasil diperbarui.</div>';
                $aksi = 'daftar';
            } else {
                $pesan = '<div class="alert alert-error">Error memperbarui kategori: ' . mysqli_error($koneksi) . '</div>';
            }
        }
    } else if (isset($_POST['hapus_kategori'])) {
        $id = (int)$_POST['id_kategori'];

        // Check if category is used by any books
        $check_query = "SELECT COUNT(*) as jumlah FROM buku WHERE id_kategori = $id";
        $check_result = mysqli_query($koneksi, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);

        if ($check_row['jumlah'] > 0) {
            $pesan = '<div class="alert alert-error">Tidak dapat menghapus kategori karena masih digunakan oleh ' . $check_row['jumlah'] . ' buku.</div>';
        } else {
            // Get icon filename
            $icon_query = "SELECT ikon FROM kategori WHERE id = $id";
            $icon_result = mysqli_query($koneksi, $icon_query);
            $icon = mysqli_fetch_assoc($icon_result)['ikon'];

            // Delete category
            $query = "DELETE FROM kategori WHERE id = $id";

            if (mysqli_query($koneksi, $query)) {
                // Delete icon file
                if ($icon && file_exists('../assets/images/kategori/' . $icon)) {
                    unlink('../assets/images/kategori/' . $icon);
                }

                $pesan = '<div class="alert alert-success">Kategori berhasil dihapus.</div>';
            } else {
                $pesan = '<div class="alert alert-error">Error menghapus kategori: ' . mysqli_error($koneksi) . '</div>';
            }
        }

        $aksi = 'daftar';
    }
}

// Get category data for edit form
$kategori = null;
if ($aksi == 'edit' && $id_kategori > 0) {
    $query = "SELECT * FROM kategori WHERE id = $id_kategori";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $kategori = mysqli_fetch_assoc($result);
    } else {
        $aksi = 'daftar';
    }
}

// Get categories for list view with search
$categories_query = "SELECT * FROM kategori";
if (!empty($cari)) {
    $categories_query .= " WHERE nama LIKE '%$cari%'";
}
$categories_query .= " ORDER BY nama ASC";
$categories_result = mysqli_query($koneksi, $categories_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $aksi == 'tambah' ? 'Tambah Kategori Baru' : ($aksi == 'edit' ? 'Edit Kategori' : 'Kelola Kategori'); ?></h1>
                <?php if ($aksi == 'daftar'): ?>
                    <a href="?aksi=tambah" class="btn btn-primary">Tambah Kategori Baru</a>
                <?php endif; ?>
            </div>

            <?php echo $pesan; ?>

            <?php if ($aksi == 'tambah' || $aksi == 'edit'): ?>
                <div class="admin-form-container">
                    <form method="post" enctype="multipart/form-data" class="admin-form">
                        <?php if ($aksi == 'edit'): ?>
                            <input type="hidden" name="id_kategori" value="<?php echo $kategori['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nama">Nama Kategori</label>
                            <input type="text" id="nama" name="nama" value="<?php echo $aksi == 'edit' ? htmlspecialchars($kategori['nama']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="ikon">Ikon Kategori</label>
                            <?php if ($aksi == 'edit' && $kategori['ikon']): ?>
                                <div class="current-image">
                                    <img src="../assets/images/kategori/<?php echo $kategori['ikon']; ?>" alt="Ikon Saat Ini">
                                    <p>Ikon kategori saat ini</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="ikon" name="ikon" accept="image/*" <?php echo $aksi == 'tambah' ? 'required' : ''; ?>>
                            <?php if ($aksi == 'edit'): ?>
                                <p class="form-help">Biarkan kosong untuk mempertahankan ikon saat ini</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="<?php echo $aksi == 'tambah' ? 'tambah_kategori' : 'update_kategori'; ?>" class="btn btn-primary">
                                <?php echo $aksi == 'tambah' ? 'Tambah Kategori' : 'Perbarui Kategori'; ?>
                            </button>
                            <a href="kategori.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="search-filters">
                    <form method="get" action="">
                        <div class="form-group">
                            <label for="cari">Cari Kategori</label>
                            <input type="text" id="cari" name="cari" placeholder="Nama kategori" value="<?php echo htmlspecialchars($cari); ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Cari</button>
                            <a href="kategori.php" class="btn btn-reset">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ikon</th>
                                <th>Nama Kategori</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($categories_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($categories_result)): ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['ikon']): ?>
                                                <img src="../assets/images/kategori/<?php echo $row['ikon']; ?>" alt="<?php echo htmlspecialchars($row['nama']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; background-color: #f1f1f1; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                    <span>N/A</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_dibuat'])); ?></td>
                                        <td>
                                            <a href="?aksi=edit&id=<?php echo $row['id']; ?>" class="btn btn-small">Edit</a>
                                            <form method="post" class="delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                                <input type="hidden" name="id_kategori" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="hapus_kategori" class="btn btn-small btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Tidak ada kategori yang ditemukan.</td>
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