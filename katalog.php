<?php
session_start();
include 'config/koneksi.php';

// Pagination
$buku_per_halaman = 12;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman - 1) * $buku_per_halaman;

// Filtering
$filter_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$kata_kunci = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';

// Base query
$query = "SELECT b.*, k.nama as nama_kategori FROM buku b 
          JOIN kategori k ON b.id_kategori = k.id WHERE 1=1";

// Add filters
if ($filter_kategori > 0) {
    $query .= " AND b.id_kategori = $filter_kategori";
}

if (!empty($kata_kunci)) {
    $query .= " AND (b.judul LIKE '%$kata_kunci%' OR b.penulis LIKE '%$kata_kunci%')";
}

// Count total books for pagination
$count_query = str_replace("b.*, k.nama as nama_kategori", "COUNT(*) as total", $query);
$count_result = mysqli_query($koneksi, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_buku = $count_row['total'];
$total_halaman = ceil($total_buku / $buku_per_halaman);

// Final query with pagination
$query .= " ORDER BY b.judul ASC LIMIT $offset, $buku_per_halaman";
$result = mysqli_query($koneksi, $query);

// Get all categories for filter
$kategori_query = "SELECT * FROM kategori ORDER BY nama ASC";
$kategori_result = mysqli_query($koneksi, $kategori_query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Digital - Katalog Buku</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="catalog-header">
            <h1>Katalog Buku</h1>
            <p>Jelajahi koleksi buku kami</p>
        </div>

        <div class="catalog-filters">
            <form action="katalog.php" method="GET" class="filter-form">
                <div class="search-box">
                    <input type="text" name="cari" placeholder="Cari judul atau penulis" value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>

                <div class="category-filter">
                    <select name="kategori" onchange="this.form.submit()">
                        <option value="0">Semua Kategori</option>
                        <?php
                        while ($kategori = mysqli_fetch_assoc($kategori_result)) {
                            $selected = ($filter_kategori == $kategori['id']) ? 'selected' : '';
                            echo '<option value="' . $kategori['id'] . '" ' . $selected . '>' . htmlspecialchars($kategori['nama']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="book-grid">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    include 'includes/kartu-buku.php';
                }
            } else {
                echo '<div class="no-results">Tidak ada buku yang sesuai dengan kriteria pencarian Anda.</div>';
            }
            ?>
        </div>

        <?php if ($total_halaman > 1): ?>
            <div class="pagination">
                <?php if ($halaman > 1): ?>
                    <a href="?halaman=<?php echo $halaman - 1; ?><?php echo $filter_kategori ? '&kategori=' . $filter_kategori : ''; ?><?php echo $kata_kunci ? '&cari=' . urlencode($kata_kunci) : ''; ?>" class="pagination-link">&laquo; Sebelumnya</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                    <?php if ($i == $halaman): ?>
                        <span class="pagination-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?halaman=<?php echo $i; ?><?php echo $filter_kategori ? '&kategori=' . $filter_kategori : ''; ?><?php echo $kata_kunci ? '&cari=' . urlencode($kata_kunci) : ''; ?>" class="pagination-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($halaman < $total_halaman): ?>
                    <a href="?halaman=<?php echo $halaman + 1; ?><?php echo $filter_kategori ? '&kategori=' . $filter_kategori : ''; ?><?php echo $kata_kunci ? '&cari=' . urlencode($kata_kunci) : ''; ?>" class="pagination-link">Selanjutnya &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>