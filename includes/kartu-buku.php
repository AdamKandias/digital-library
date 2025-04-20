<a href="detail-buku.php?id=<?php echo $row['id']; ?>" class="book-card">
    <div class="book-cover">
        <img src="<?php echo $row['sampul'] ? 'assets/images/buku/' . $row['sampul'] : 'assets/images/sampul-default.jpg'; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
    </div>
    <div class="book-info">
        <h3><?php echo htmlspecialchars($row['judul']); ?></h3>
        <p class="author"><?php echo htmlspecialchars($row['penulis']); ?></p>
        <div class="book-meta">
            <span class="book-category"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
            <span class="book-year"><?php echo $row['tahun_terbit']; ?></span>
        </div>
    </div>
</a>