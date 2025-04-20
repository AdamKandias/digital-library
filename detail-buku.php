<?php
session_start();
include 'config/koneksi.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: katalog.php');
    exit;
}

$id_buku = (int)$_GET['id'];
$query = "SELECT b.*, k.nama as nama_kategori FROM buku b 
          JOIN kategori k ON b.id_kategori = k.id 
          WHERE b.id = $id_buku";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: katalog.php');
    exit;
}

$buku = mysqli_fetch_assoc($result);

// Ambil semua gambar buku
$gambar_query = "SELECT * FROM gambar_buku WHERE id_buku = $id_buku ORDER BY urutan ASC";
$gambar_result = mysqli_query($koneksi, $gambar_query);
$gambar_buku = [];
while ($row = mysqli_fetch_assoc($gambar_result)) {
    $gambar_buku[] = $row;
}

// Check if book is available
$available_query = "SELECT COUNT(*) as dipinjam FROM peminjaman 
                   WHERE id_buku = $id_buku AND status = 'dipinjam'";
$available_result = mysqli_query($koneksi, $available_query);
$available_row = mysqli_fetch_assoc($available_result);
$jumlah_dipinjam = $available_row['dipinjam'];
$stok_tersedia = $buku['stok'] - $jumlah_dipinjam;

// Check if user has already borrowed this book
$sudah_pinjam = false;
if (isset($_SESSION['id_pengguna'])) {
    $id_pengguna = $_SESSION['id_pengguna'];
    $user_borrow_query = "SELECT * FROM peminjaman 
                         WHERE id_pengguna = $id_pengguna AND id_buku = $id_buku AND status = 'dipinjam'";
    $user_borrow_result = mysqli_query($koneksi, $user_borrow_query);
    $sudah_pinjam = mysqli_num_rows($user_borrow_result) > 0;
}

// Process borrow request
$pesan_pinjam = '';
if (isset($_POST['pinjam']) && isset($_SESSION['id_pengguna'])) {
    $id_pengguna = $_SESSION['id_pengguna'];

    // Check if user has any penalties
    $penalty_query = "SELECT * FROM denda 
                     WHERE id_pengguna = $id_pengguna AND status = 'belum_dibayar'";
    $penalty_result = mysqli_query($koneksi, $penalty_query);

    if (mysqli_num_rows($penalty_result) > 0) {
        $pesan_pinjam = '<div class="alert alert-error">Anda memiliki denda yang belum dibayar. Harap selesaikan pembayaran sebelum meminjam.</div>';
    } else if ($stok_tersedia <= 0) {
        $pesan_pinjam = '<div class="alert alert-error">Tidak ada stok buku yang tersedia untuk dipinjam.</div>';
    } else if ($sudah_pinjam) {
        $pesan_pinjam = '<div class="alert alert-error">Anda sudah meminjam buku ini.</div>';
    } else {
        // Check how many books the user has borrowed
        $active_borrows_query = "SELECT COUNT(*) as jumlah FROM peminjaman 
                               WHERE id_pengguna = $id_pengguna AND status = 'dipinjam'";
        $active_borrows_result = mysqli_query($koneksi, $active_borrows_query);
        $active_borrows_row = mysqli_fetch_assoc($active_borrows_result);

        if ($active_borrows_row['jumlah'] >= 3) {
            $pesan_pinjam = '<div class="alert alert-error">Anda tidak dapat meminjam lebih dari 3 buku sekaligus.</div>';
        } else {
            // Process the borrowing
            $tanggal_pinjam = date('Y-m-d');
            $tanggal_kembali = date('Y-m-d', strtotime('+14 days'));

            $borrow_query = "INSERT INTO peminjaman (id_pengguna, id_buku, tanggal_pinjam, tanggal_jatuh_tempo, status) 
                           VALUES ($id_pengguna, $id_buku, '$tanggal_pinjam', '$tanggal_kembali', 'dipinjam')";

            if (mysqli_query($koneksi, $borrow_query)) {
                $pesan_pinjam = '<div class="alert alert-success">Buku berhasil dipinjam. Harap kembalikan sebelum ' . date('d/m/Y', strtotime($tanggal_kembali)) . '.</div>';
                $sudah_pinjam = true;
                $stok_tersedia--;
            } else {
                $pesan_pinjam = '<div class="alert alert-error">Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi.</div>';
            }
        }
    }
}

// Get similar books
$similar_query = "SELECT b.*, k.nama as nama_kategori FROM buku b 
                 JOIN kategori k ON b.id_kategori = k.id 
                 WHERE b.id_kategori = {$buku['id_kategori']} AND b.id != $id_buku 
                 LIMIT 4";
$similar_result = mysqli_query($koneksi, $similar_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($buku['judul']); ?> - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .book-gallery {
            margin-bottom: 20px;
        }

        .book-main-image {
            width: 100%;
            margin-bottom: 10px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .book-thumbnails {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .book-thumbnail {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius);
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .book-thumbnail:hover,
        .book-thumbnail.active {
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <?php if (!empty($pesan_pinjam)) echo $pesan_pinjam; ?>

        <div class="book-details">
            <div class="book-image">
                <?php if (count($gambar_buku) > 0): ?>
                    <div class="book-gallery">
                        <img id="mainImage" src="assets/images/buku/<?php echo $gambar_buku[0]['nama_file']; ?>" alt="<?php echo htmlspecialchars($buku['judul']); ?>" class="book-main-image">

                        <?php if (count($gambar_buku) > 1): ?>
                            <div class="book-thumbnails">
                                <?php foreach ($gambar_buku as $index => $gambar): ?>
                                    <img
                                        src="assets/images/buku/<?php echo $gambar['nama_file']; ?>"
                                        alt="<?php echo htmlspecialchars($buku['judul']) . ' - Gambar ' . ($index + 1); ?>"
                                        class="book-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                        onclick="changeMainImage(this, '<?php echo $gambar['nama_file']; ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <img src="<?php echo $buku['sampul'] ? 'assets/images/buku/' . $buku['sampul'] : 'assets/images/sampul-default.jpg'; ?>" alt="<?php echo htmlspecialchars($buku['judul']); ?>">
                <?php endif; ?>
            </div>

            <div class="book-info">
                <h1><?php echo htmlspecialchars($buku['judul']); ?></h1>
                <p class="book-author">Oleh <?php echo htmlspecialchars($buku['penulis']); ?></p>

                <div class="book-meta">
                    <span class="book-category"><?php echo htmlspecialchars($buku['nama_kategori']); ?></span>
                    <span class="book-year"><?php echo $buku['tahun_terbit']; ?></span>
                    <span class="book-publisher"><?php echo htmlspecialchars($buku['penerbit']); ?></span>
                </div>

                <div class="book-availability">
                    <?php if ($stok_tersedia > 0): ?>
                        <span class="available">Tersedia (<?php echo $stok_tersedia; ?> buku)</span>
                    <?php else: ?>
                        <span class="unavailable">Tidak Tersedia</span>
                    <?php endif; ?>
                </div>

                <div class="book-description">
                    <h3>Deskripsi</h3>
                    <p><?php echo nl2br(htmlspecialchars($buku['deskripsi'])); ?></p>
                </div>

                <div class="book-actions">
                    <?php if (isset($_SESSION['id_pengguna'])): ?>
                        <?php if ($sudah_pinjam): ?>
                            <button class="btn btn-secondary" disabled>Sudah Dipinjam</button>
                        <?php else: ?>
                            <?php if ($_SESSION['peran_pengguna'] != 'admin'): ?>
                                <form method="post">
                                    <button type="submit" name="pinjam" class="btn btn-primary" <?php echo $stok_tersedia <= 0 ? 'disabled' : ''; ?>>
                                        Pinjam Buku
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="masuk.php?redirect=detail-buku.php?id=<?php echo $id_buku; ?>">
                            <button class="btn btn-primary">
                                Masuk untuk Meminjam
                            </button>
                        </a>
                    <?php endif; ?>
                    <a href="katalog.php" style="color: white;">
                        <button class="btn btn-secondary">
                            Kembali ke Katalog
                        </button>
                    </a>
                </div>
            </div>
        </div>

        <section class="similar-books">
            <h2>Buku Serupa</h2>
            <div class="book-grid">
                <?php
                while ($row = mysqli_fetch_assoc($similar_result)) {
                    include 'includes/kartu-buku.php';
                }
                ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function changeMainImage(thumbnail, filename) {
            // Update main image
            document.getElementById('mainImage').src = 'assets/images/buku/' + filename;

            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.book-thumbnail');
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
    </script>
</body>

</html>