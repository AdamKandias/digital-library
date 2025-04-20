<?php
session_start();
include '../config/koneksi.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['peran_pengguna'] != 'admin') {
    header('Location: ../masuk.php');
    exit;
}

$pesan = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update each setting
        $durasi_peminjaman = (int)$_POST['durasi_peminjaman'];
        $denda_per_hari = (int)$_POST['denda_per_hari'];
        $denda_buku_hilang = (int)$_POST['denda_buku_hilang'];
        $max_peminjaman = (int)$_POST['max_peminjaman'];

        // Update settings in database
        $settings = [
            ['durasi_peminjaman', $durasi_peminjaman],
            ['denda_per_hari', $denda_per_hari],
            ['denda_buku_hilang', $denda_buku_hilang],
            ['max_peminjaman', $max_peminjaman],
        ];

        foreach ($settings as $setting) {
            $nama = $setting[0];
            $nilai = $setting[1];

            $update_query = "UPDATE pengaturan SET nilai = '$nilai' WHERE nama = '$nama'";
            mysqli_query($koneksi, $update_query);
        }

        $pesan = '<div class="alert alert-success">Pengaturan berhasil diperbarui.</div>';
    }
}

// Get current settings
$settings_query = "SELECT * FROM pengaturan";
$settings_result = mysqli_query($koneksi, $settings_query);
$settings = [];

while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['nama']] = $row['nilai'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Pengaturan Sistem</h1>
            </div>

            <?php echo $pesan; ?>

            <div class="admin-form-container">
                <form method="post" class="admin-form">
                    <div class="settings-section">
                        <h2>Pengaturan Peminjaman</h2>

                        <div class="form-group">
                            <label for="durasi_peminjaman">Durasi Peminjaman (hari)</label>
                            <input type="number" id="durasi_peminjaman" name="durasi_peminjaman" min="1" max="60" value="<?php echo isset($settings['durasi_peminjaman']) ? $settings['durasi_peminjaman'] : 14; ?>" required>
                            <p class="form-help">Jumlah hari anggota dapat meminjam buku sebelum harus dikembalikan.</p>
                        </div>

                        <div class="form-group">
                            <label for="denda_per_hari">Denda per Hari (Rp)</label>
                            <input type="number" id="denda_per_hari" name="denda_per_hari" min="0" value="<?php echo isset($settings['denda_per_hari']) ? $settings['denda_per_hari'] : 10000; ?>" required>
                            <p class="form-help">Jumlah denda yang dikenakan per hari keterlambatan.</p>
                        </div>

                        <div class="form-group">
                            <label for="denda_buku_hilang">Denda Buku Hilang (Rp)</label>
                            <input type="number" id="denda_buku_hilang" name="denda_buku_hilang" min="0" value="<?php echo isset($settings['denda_buku_hilang']) ? $settings['denda_buku_hilang'] : 100000; ?>" required>
                            <p class="form-help">Jumlah denda yang dikenakan jika buku hilang atau rusak parah.</p>
                        </div>

                        <div class="form-group">
                            <label for="max_peminjaman">Maksimum Peminjaman</label>
                            <input type="number" id="max_peminjaman" name="max_peminjaman" min="1" max="10" value="<?php echo isset($settings['max_peminjaman']) ? $settings['max_peminjaman'] : 3; ?>" required>
                            <p class="form-help">Jumlah maksimum buku yang dapat dipinjam oleh satu anggota secara bersamaan.</p>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_settings" class="btn btn-primary">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <style>
        .settings-section h2 {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .form-help {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 5px;
        }
    </style>
</body>

</html>