<?php
session_start();
include 'config/koneksi.php';

$pesan = '';

// Proses form kontak
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $subjek = mysqli_real_escape_string($koneksi, $_POST['subjek']);
    $pesan_teks = mysqli_real_escape_string($koneksi, $_POST['pesan']);

    // Validasi input
    if (empty($nama) || empty($email) || empty($subjek) || empty($pesan_teks)) {
        $pesan = '<div class="alert alert-error">Semua kolom harus diisi.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = '<div class="alert alert-error">Format email tidak valid.</div>';
    } else {
        // Simpan pesan ke database (jika ada tabel pesan)
        // Atau kirim email ke admin (dalam implementasi nyata)

        // Untuk demo, kita hanya tampilkan pesan sukses
        $pesan = '<div class="alert alert-success">Terima kasih! Pesan Anda telah dikirim. Kami akan menghubungi Anda segera.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        .contact-form-container {
            background-color: var(--bg-color);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .contact-info {
            background-color: var(--bg-color);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .contact-info-item {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .contact-icon svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        .contact-text h4 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .map-container {
            margin-top: 30px;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: 0;
        }

        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>Hubungi Kami</h1>
            <p>Punya pertanyaan atau masukan? Jangan ragu untuk menghubungi kami.</p>
        </div>

        <?php echo $pesan; ?>

        <div class="contact-container">
            <div class="contact-form-container">
                <h2>Kirim Pesan</h2>
                <form method="post" class="contact-form">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="subjek">Subjek</label>
                        <input type="text" id="subjek" name="subjek" required>
                    </div>

                    <div class="form-group">
                        <label for="pesan">Pesan</label>
                        <textarea id="pesan" name="pesan" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                </form>
            </div>

            <div class="contact-info">
                <h2>Informasi Kontak</h2>

                <div class="contact-info-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z"></path>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <h4>Alamat</h4>
                        <p>Jl. Perpustakaan No. 123, Surabaya, Kampus PENS JOSS</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"></path>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <h4>Telepon</h4>
                        <p>+62 123 4567 890</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"></path>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <h4>Email</h4>
                        <p>info@perpustakaandigital.com</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8"></path>
                        </svg>
                    </div>
                    <div class="contact-text">
                        <h4>Jam Operasional</h4>
                        <p>Senin - Jumat: 08.00 - 20.00<br>
                            Sabtu: 09.00 - 17.00<br>
                            Minggu: Tutup</p>
                    </div>
                </div>

                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d735.6283969754718!2d112.79316171491006!3d-7.276058605431983!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fa10ea2ae883%3A0xbe22c55d60ef09c7!2s(wa%200819%205287%200331)Politeknik%20Elektronika%20Negeri%20Surabaya!5e1!3m2!1sid!2sid!4v1745053510981!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>