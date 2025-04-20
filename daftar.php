<?php
session_start();
include 'config/koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['id_pengguna'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi_password)) {
        $error = 'Harap isi semua kolom';
    } else if ($password != $konfirmasi_password) {
        $error = 'Password dan konfirmasi tidak cocok';
    } else if (strlen($password) < 6) {
        $error = 'Password harus minimal 6 karakter';
    } else {
        // Check if email already exists
        $check_query = "SELECT * FROM pengguna WHERE email = '$email'";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email sudah terdaftar';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert_query = "INSERT INTO pengguna (nama, email, password, peran, tanggal_daftar) 
                           VALUES ('$nama', '$email', '$hashed_password', 'anggota', NOW())";

            if (mysqli_query($koneksi, $insert_query)) {
                $success = 'Pendaftaran berhasil! Anda sekarang dapat masuk.';
            } else {
                $error = 'Pendaftaran gagal. Silakan coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="auth-container">
            <div class="auth-form-container">
                <h1>Buat Akun</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required value="<?php echo empty($success) ? htmlspecialchars($nama ?? '') : '' ?>">
                    </div>

                    <div class=" form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo empty($success) ? htmlspecialchars($email ?? '') : '' ?>">
                    </div>

                    <div class=" form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Daftar</button>
                </form>

                <div class="auth-links">
                    <p>Sudah punya akun? <a href="masuk.php">Masuk</a></p>
                </div>
            </div>

            <div class="auth-image">
                <img src="assets/images/library-register.jpg" alt="Gambar Perpustakaan">
                <div class="auth-overlay">
                    <h2>Bergabung dengan Perpustakaan Kami</h2>
                    <p>Buat akun untuk mengakses perpustakaan digital kami dan pinjam buku secara online.</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>