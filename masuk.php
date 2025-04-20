<?php
session_start();
include 'config/koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['id_pengguna'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Harap isi semua kolom';
    } else {
        $query = "SELECT * FROM pengguna WHERE email = '$email'";
        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) == 1) {
            $pengguna = mysqli_fetch_assoc($result);

            if (password_verify($password, $pengguna['password'])) {
                $_SESSION['id_pengguna'] = $pengguna['id'];
                $_SESSION['nama_pengguna'] = $pengguna['nama'];
                $_SESSION['email_pengguna'] = $pengguna['email'];
                $_SESSION['peran_pengguna'] = $pengguna['peran'];

                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Password tidak valid';
            }
        } else {
            $error = 'Pengguna tidak ditemukan';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="auth-container">
            <div class="auth-form-container">
                <h1>Masuk</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                </form>

                <div class="auth-links">
                    <p>Belum punya akun? <a href="daftar.php">Daftar</a></p>
                </div>
            </div>

            <div class="auth-image">
                <img src="assets/images/library-login.jpg" alt="Gambar Perpustakaan">
                <div class="auth-overlay">
                    <h2>Selamat Datang Kembali</h2>
                    <p>Akses akun perpustakaan digital Anda untuk meminjam buku dan mengelola daftar bacaan Anda.</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>