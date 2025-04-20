<?php
session_start();
include 'config/koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .about-section {
            background-color: var(--bg-color);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .about-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .about-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .about-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

        .vision-mission {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }

        .vision-card,
        .mission-card {
            background-color: var(--bg-light);
            padding: 20px;
            border-radius: var(--radius);
        }

        .vision-card h3,
        .mission-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .team-member {
            background-color: var(--bg-light);
            border-radius: var(--radius);
            overflow: hidden;
            text-align: center;
        }

        .team-member img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .team-info {
            padding: 15px;
        }

        .team-info h3 {
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .team-info p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 30px auto;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: var(--primary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: white;
            border: 4px solid var(--primary-color);
            border-radius: 50%;
            top: 15px;
            z-index: 1;
        }

        .timeline-left {
            left: 0;
        }

        .timeline-right {
            left: 50%;
        }

        .timeline-left::after {
            right: -10px;
        }

        .timeline-right::after {
            left: -10px;
        }

        .timeline-content {
            padding: 20px;
            background-color: var(--bg-light);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .timeline-content h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .vision-mission {
                grid-template-columns: 1fr;
            }

            .timeline::after {
                left: 31px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }

            .timeline-right {
                left: 0;
            }

            .timeline-left::after,
            .timeline-right::after {
                left: 21px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <div class="about-header">
            <h1>Tentang Perpustakaan Digital</h1>
            <p>Menyediakan akses pengetahuan untuk semua</p>
        </div>

        <div class="about-section">
            <img src="assets/images/library-tentang.jpg" alt="Perpustakaan Digital" class="about-image">

            <h2>Selamat Datang di Perpustakaan Digital</h2>
            <p>Perpustakaan Digital adalah platform inovatif yang didedikasikan untuk menyediakan akses mudah ke berbagai koleksi buku dan sumber pengetahuan dalam format digital. Kami berkomitmen untuk menjembatani kesenjangan informasi dan mempromosikan budaya membaca di era digital.</p>
            <p>Didirikan pada tahun 2021, Perpustakaan Digital telah berkembang menjadi salah satu platform perpustakaan digital terkemuka di Indonesia dengan lebih dari 10.000 koleksi buku dari berbagai kategori dan genre.</p>

            <div class="vision-mission">
                <div class="vision-card">
                    <h3>Visi</h3>
                    <p>Menjadi platform perpustakaan digital terdepan yang menyediakan akses pengetahuan berkualitas untuk semua lapisan masyarakat, mendukung pembelajaran sepanjang hayat, dan membangun masyarakat yang berpengetahuan.</p>
                </div>

                <div class="mission-card">
                    <h3>Misi</h3>
                    <ul>
                        <li>Menyediakan akses mudah ke berbagai sumber pengetahuan dalam format digital</li>
                        <li>Mempromosikan budaya membaca dan pembelajaran sepanjang hayat</li>
                        <li>Mendukung pendidikan dan penelitian melalui koleksi yang komprehensif</li>
                        <li>Membangun komunitas pembaca yang aktif dan berpengetahuan</li>
                        <li>Berkolaborasi dengan penerbit, penulis, dan institusi pendidikan</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Sejarah Kami</h2>
            <p>Berikut adalah perjalanan Perpustakaan Digital dari awal hingga saat ini:</p>

            <div class="timeline">
                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>2021</h3>
                        <p>Perpustakaan Digital didirikan dengan koleksi awal 500 buku digital.</p>
                    </div>
                </div>

                <div class="timeline-item timeline-right">
                    <div class="timeline-content">
                        <h3>2022</h3>
                        <p>Peluncuran platform peminjaman buku digital dan mencapai 5.000 anggota terdaftar.</p>
                    </div>
                </div>

                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>2023</h3>
                        <p>Ekspansi koleksi ke 5.000 judul buku dan kemitraan dengan 10 penerbit besar.</p>
                    </div>
                </div>

                <div class="timeline-item timeline-right">
                    <div class="timeline-content">
                        <h3>2024</h3>
                        <p>Peluncuran aplikasi mobile dan fitur audio book untuk meningkatkan aksesibilitas.</p>
                    </div>
                </div>

                <div class="timeline-item timeline-left">
                    <div class="timeline-content">
                        <h3>2025</h3>
                        <p>Mencapai 10.000 koleksi buku dan 50.000 anggota aktif di seluruh Indonesia.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Tim Kami</h2>
            <p>Perpustakaan Digital dikelola oleh tim profesional yang berdedikasi untuk menyediakan layanan perpustakaan digital terbaik:</p>

            <div class="team-grid">
                <div class="team-member">
                    <img src="assets/images/team/direktur.jpg" alt="Direktur">
                    <div class="team-info">
                        <h3>Dr. Budi Santoso</h3>
                        <p>Direktur</p>
                    </div>
                </div>

                <div class="team-member">
                    <img src="assets/images/team/pustakawan.jpg" alt="Kepala Pustakawan">
                    <div class="team-info">
                        <h3>Siti Rahayu, M.Lib</h3>
                        <p>Kepala Pustakawan</p>
                    </div>
                </div>

                <div class="team-member">
                    <img src="assets/images/team/teknologi.jpg" alt="Kepala Teknologi">
                    <div class="team-info">
                        <h3>Andi Wijaya, M.Kom</h3>
                        <p>Kepala Teknologi</p>
                    </div>
                </div>

                <div class="team-member">
                    <img src="assets/images/team/kemitraan.jpg" alt="Manajer Kemitraan">
                    <div class="team-info">
                        <h3>Maya Indah, MBA</h3>
                        <p>Manajer Kemitraan</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>