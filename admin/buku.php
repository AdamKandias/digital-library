<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'daftar';
$id_buku = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = '';

// Search and filter parameters
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
$filter_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$filter_stok = isset($_GET['stok']) ? $_GET['stok'] : '';

// Get categories for form
$kategori_query = "SELECT * FROM kategori ORDER BY nama ASC";
$kategori_result = mysqli_query($koneksi, $kategori_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_buku']) || isset($_POST['update_buku'])) {
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
        $penulis = mysqli_real_escape_string($koneksi, $_POST['penulis']);
        $penerbit = mysqli_real_escape_string($koneksi, $_POST['penerbit']);
        $tahun = (int)$_POST['tahun'];
        $id_kategori = (int)$_POST['id_kategori'];
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $stok = (int)$_POST['stok'];

        // Handle file upload for main cover
        $sampul = '';
        if (isset($_FILES['sampul']) && $_FILES['sampul']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['sampul']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = '../assets/images/buku/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['sampul']['tmp_name'], $upload_dir . $new_filename)) {
                    $sampul = $new_filename;
                }
            }
        }

        if (isset($_POST['tambah_buku'])) {
            // Add new book
            $query = "INSERT INTO buku (judul, penulis, penerbit, tahun_terbit, id_kategori, deskripsi, stok, sampul, tanggal_ditambahkan) 
                     VALUES ('$judul', '$penulis', '$penerbit', $tahun, $id_kategori, '$deskripsi', $stok, '$sampul', NOW())";

            if (mysqli_query($koneksi, $query)) {
                $id_buku_baru = mysqli_insert_id($koneksi);

                // Handle additional images
                if (isset($_FILES['gambar_tambahan']) && is_array($_FILES['gambar_tambahan']['name'])) {
                    $total_files = count($_FILES['gambar_tambahan']['name']);

                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['gambar_tambahan']['error'][$i] == 0) {
                            $filename = $_FILES['gambar_tambahan']['name'][$i];
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);

                            if (in_array(strtolower($ext), $allowed)) {
                                $new_filename = uniqid() . '.' . $ext;

                                if (move_uploaded_file($_FILES['gambar_tambahan']['tmp_name'][$i], $upload_dir . $new_filename)) {
                                    $urutan = $i + 1;
                                    $gambar_query = "INSERT INTO gambar_buku (id_buku, nama_file, urutan) VALUES ($id_buku_baru, '$new_filename', $urutan)";
                                    mysqli_query($koneksi, $gambar_query);
                                }
                            }
                        }
                    }
                }

                $pesan = '<div class="alert alert-success">Buku berhasil ditambahkan.</div>';
                $aksi = 'daftar';
            } else {
                $pesan = '<div class="alert alert-error">Error menambahkan buku: ' . mysqli_error($koneksi) . '</div>';
            }
        } else if (isset($_POST['update_buku'])) {
            // Update existing book
            $id = (int)$_POST['id_buku'];

            if ($sampul) {
                $query = "UPDATE buku SET judul = '$judul', penulis = '$penulis', penerbit = '$penerbit', 
                         tahun_terbit = $tahun, id_kategori = $id_kategori, deskripsi = '$deskripsi', 
                         stok = $stok, sampul = '$sampul' WHERE id = $id";
            } else {
                $query = "UPDATE buku SET judul = '$judul', penulis = '$penulis', penerbit = '$penerbit', 
                         tahun_terbit = $tahun, id_kategori = $id_kategori, deskripsi = '$deskripsi', 
                         stok = $stok WHERE id = $id";
            }

            if (mysqli_query($koneksi, $query)) {
                // Handle additional images
                if (isset($_FILES['gambar_tambahan']) && is_array($_FILES['gambar_tambahan']['name'])) {
                    $total_files = count($_FILES['gambar_tambahan']['name']);

                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['gambar_tambahan']['error'][$i] == 0) {
                            $filename = $_FILES['gambar_tambahan']['name'][$i];
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);

                            if (in_array(strtolower($ext), $allowed)) {
                                $new_filename = uniqid() . '.' . $ext;

                                if (move_uploaded_file($_FILES['gambar_tambahan']['tmp_name'][$i], $upload_dir . $new_filename)) {
                                    // Get max order
                                    $max_order_query = "SELECT MAX(urutan) as max_urutan FROM gambar_buku WHERE id_buku = $id";
                                    $max_order_result = mysqli_query($koneksi, $max_order_query);
                                    $max_order = mysqli_fetch_assoc($max_order_result)['max_urutan'];
                                    $urutan = $max_order ? $max_order + 1 : 1;

                                    $gambar_query = "INSERT INTO gambar_buku (id_buku, nama_file, urutan) VALUES ($id, '$new_filename', $urutan)";
                                    mysqli_query($koneksi, $gambar_query);
                                }
                            }
                        }
                    }
                }

                $pesan = '<div class="alert alert-success">Buku berhasil diperbarui.</div>';
                $aksi = 'daftar';
            } else {
                $pesan = '<div class="alert alert-error">Error memperbarui buku: ' . mysqli_error($koneksi) . '</div>';
            }
        }
    } else if (isset($_POST['hapus_buku'])) {
        $id = (int)$_POST['id_buku'];

        // Check if book is currently borrowed
        $check_query = "SELECT COUNT(*) as jumlah FROM peminjaman WHERE id_buku = $id AND status = 'dipinjam'";
        $check_result = mysqli_query($koneksi, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);

        if ($check_row['jumlah'] > 0) {
            $pesan = '<div class="alert alert-error">Tidak dapat menghapus buku karena sedang dipinjam.</div>';
        } else {
            // Get all images to delete files
            $images_query = "SELECT nama_file FROM gambar_buku WHERE id_buku = $id";
            $images_result = mysqli_query($koneksi, $images_query);
            $images = [];
            while ($row = mysqli_fetch_assoc($images_result)) {
                $images[] = $row['nama_file'];
            }

            // Get main cover
            $cover_query = "SELECT sampul FROM buku WHERE id = $id";
            $cover_result = mysqli_query($koneksi, $cover_query);
            $cover = mysqli_fetch_assoc($cover_result)['sampul'];

            // Delete book (will cascade delete images)
            $query = "DELETE FROM buku WHERE id = $id";

            if (mysqli_query($koneksi, $query)) {
                // Delete image files
                $upload_dir = '../assets/images/buku/';
                foreach ($images as $image) {
                    if (file_exists($upload_dir . $image)) {
                        unlink($upload_dir . $image);
                    }
                }

                // Delete cover file
                if ($cover && file_exists($upload_dir . $cover)) {
                    unlink($upload_dir . $cover);
                }

                $pesan = '<div class="alert alert-success">Buku berhasil dihapus.</div>';
            } else {
                $pesan = '<div class="alert alert-error">Error menghapus buku: ' . mysqli_error($koneksi) . '</div>';
            }
        }

        $aksi = 'daftar';
    } else if (isset($_POST['hapus_gambar'])) {
        $id_gambar = (int)$_POST['id_gambar'];
        $id_buku = (int)$_POST['id_buku'];

        // Get image filename
        $image_query = "SELECT nama_file FROM gambar_buku WHERE id = $id_gambar";
        $image_result = mysqli_query($koneksi, $image_query);
        $image = mysqli_fetch_assoc($image_result)['nama_file'];

        // Delete from database
        $query = "DELETE FROM gambar_buku WHERE id = $id_gambar";

        if (mysqli_query($koneksi, $query)) {
            // Delete file
            $upload_dir = '../assets/images/buku/';
            if (file_exists($upload_dir . $image)) {
                unlink($upload_dir . $image);
            }

            $pesan = '<div class="alert alert-success">Gambar berhasil dihapus.</div>';
        } else {
            $pesan = '<div class="alert alert-error">Error menghapus gambar: ' . mysqli_error($koneksi) . '</div>';
        }

        $aksi = 'edit';
    }
}

// Get book data for edit form
$buku = null;
$gambar_buku = [];
if ($aksi == 'edit' && $id_buku > 0) {
    $query = "SELECT * FROM buku WHERE id = $id_buku";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $buku = mysqli_fetch_assoc($result);

        // Get book images
        $gambar_query = "SELECT * FROM gambar_buku WHERE id_buku = $id_buku ORDER BY urutan ASC";
        $gambar_result = mysqli_query($koneksi, $gambar_query);
        while ($row = mysqli_fetch_assoc($gambar_result)) {
            $gambar_buku[] = $row;
        }
    } else {
        $aksi = 'daftar';
    }
}

// Build query for books list with search and filters
$books_query = "SELECT b.*, k.nama as nama_kategori FROM buku b 
               JOIN kategori k ON b.id_kategori = k.id 
               WHERE 1=1";

if (!empty($cari)) {
    $books_query .= " AND (b.judul LIKE '%$cari%' OR b.penulis LIKE '%$cari%' OR b.penerbit LIKE '%$cari%')";
}

if ($filter_kategori > 0) {
    $books_query .= " AND b.id_kategori = $filter_kategori";
}

if ($filter_stok == 'habis') {
    $books_query .= " AND b.stok = 0";
} elseif ($filter_stok == 'tersedia') {
    $books_query .= " AND b.stok > 0";
}

$books_query .= " ORDER BY b.judul ASC";
$books_result = mysqli_query($koneksi, $books_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .book-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }

        .book-image-item {
            position: relative;
            width: 120px;
        }

        .book-image-item img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: var(--radius);
        }

        .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-order {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 12px;
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
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1><?php echo $aksi == 'tambah' ? 'Tambah Buku Baru' : ($aksi == 'edit' ? 'Edit Buku' : 'Kelola Buku'); ?></h1>
                <?php if ($aksi == 'daftar'): ?>
                    <a href="?aksi=tambah" class="btn btn-primary">Tambah Buku Baru</a>
                <?php endif; ?>
            </div>

            <?php echo $pesan; ?>

            <?php if ($aksi == 'tambah' || $aksi == 'edit'): ?>
                <div class="admin-form-container">
                    <form method="post" enctype="multipart/form-data" class="admin-form">
                        <?php if ($aksi == 'edit'): ?>
                            <input type="hidden" name="id_buku" value="<?php echo $buku['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="judul">Judul</label>
                            <input type="text" id="judul" name="judul" value="<?php echo $aksi == 'edit' ? htmlspecialchars($buku['judul']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="penulis">Penulis</label>
                            <input type="text" id="penulis" name="penulis" value="<?php echo $aksi == 'edit' ? htmlspecialchars($buku['penulis']) : ''; ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="penerbit">Penerbit</label>
                                <input type="text" id="penerbit" name="penerbit" value="<?php echo $aksi == 'edit' ? htmlspecialchars($buku['penerbit']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="tahun">Tahun Terbit</label>
                                <input type="number" id="tahun" name="tahun" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $aksi == 'edit' ? $buku['tahun_terbit'] : date('Y'); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_kategori">Kategori</label>
                                <select id="id_kategori" name="id_kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php
                                    mysqli_data_seek($kategori_result, 0);
                                    while ($kategori = mysqli_fetch_assoc($kategori_result)) {
                                        $selected = ($aksi == 'edit' && $buku['id_kategori'] == $kategori['id']) ? 'selected' : '';
                                        echo '<option value="' . $kategori['id'] . '" ' . $selected . '>' . htmlspecialchars($kategori['nama']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="stok">Stok</label>
                                <input type="number" id="stok" name="stok" min="0" value="<?php echo $aksi == 'edit' ? $buku['stok'] : '1'; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="5" required><?php echo $aksi == 'edit' ? htmlspecialchars($buku['deskripsi']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="sampul">Sampul Utama</label>
                            <?php if ($aksi == 'edit' && $buku['sampul']): ?>
                                <div class="current-image">
                                    <img src="../assets/images/buku/<?php echo $buku['sampul']; ?>" alt="Sampul Saat Ini">
                                    <p>Sampul buku saat ini</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="sampul" name="sampul" accept="image/*" <?php echo $aksi == 'tambah' ? 'required' : ''; ?>>
                            <?php if ($aksi == 'edit'): ?>
                                <p class="form-help">Biarkan kosong untuk mempertahankan gambar saat ini</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="gambar_tambahan">Gambar Tambahan</label>
                            <input type="file" id="gambar_tambahan" name="gambar_tambahan[]" accept="image/*" multiple>
                            <p class="form-help">Anda dapat memilih beberapa gambar sekaligus</p>

                            <?php if ($aksi == 'edit' && count($gambar_buku) > 0): ?>
                                <h4 style="margin-top: 15px;">Gambar Saat Ini:</h4>
                                <div class="book-images">
                                    <?php foreach ($gambar_buku as $gambar): ?>
                                        <div class="book-image-item">
                                            <img src="../assets/images/buku/<?php echo $gambar['nama_file']; ?>" alt="Gambar Buku">
                                            <span class="image-order">Urutan: <?php echo $gambar['urutan']; ?></span>
                                            <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus gambar ini?');">
                                                <input type="hidden" name="id_gambar" value="<?php echo $gambar['id']; ?>">
                                                <input type="hidden" name="id_buku" value="<?php echo $id_buku; ?>">
                                                <button type="submit" name="hapus_gambar" class="delete-image">×</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="<?php echo $aksi == 'tambah' ? 'tambah_buku' : 'update_buku'; ?>" class="btn btn-primary">
                                <?php echo $aksi == 'tambah' ? 'Tambah Buku' : 'Perbarui Buku'; ?>
                            </button>
                            <a href="buku.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="search-filters">
                    <form method="get" action="">
                        <div class="form-group">
                            <label for="cari">Cari Buku</label>
                            <input type="text" id="cari" name="cari" placeholder="Judul, penulis, atau penerbit" value="<?php echo htmlspecialchars($cari); ?>">
                        </div>

                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori">
                                <option value="0">Semua Kategori</option>
                                <?php
                                mysqli_data_seek($kategori_result, 0);
                                while ($kategori = mysqli_fetch_assoc($kategori_result)) {
                                    $selected = ($filter_kategori == $kategori['id']) ? 'selected' : '';
                                    echo '<option value="' . $kategori['id'] . '" ' . $selected . '>' . htmlspecialchars($kategori['nama']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="stok">Status Stok</label>
                            <select id="stok" name="stok">
                                <option value="">Semua</option>
                                <option value="tersedia" <?php echo $filter_stok == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                <option value="habis" <?php echo $filter_stok == 'habis' ? 'selected' : ''; ?>>Habis</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="buku.php" class="btn btn-reset">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Sampul</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($books_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($books_result)): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $row['sampul'] ? '../assets/images/buku/' . $row['sampul'] : '../assets/images/sampul-default.jpg'; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>" class="book-thumbnail">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                        <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                        <td><?php echo $row['stok']; ?></td>
                                        <td>
                                            <a href="?aksi=edit&id=<?php echo $row['id']; ?>" class="btn btn-small">Edit</a>
                                            <form method="post" class="delete-form" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">
                                                <input type="hidden" name="id_buku" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="hapus_buku" class="btn btn-small btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Tidak ada buku yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>