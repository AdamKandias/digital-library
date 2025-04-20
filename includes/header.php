<header>
    <style>
        /* Responsive Header */
        @media (max-width: <?php
                            if (!isset($_SESSION['id_pengguna'])) {
                                echo '790px';
                            } else {
                                echo $_SESSION['peran_pengguna'] === 'admin' ? '832px' : '945px';
                            }
                            ?>) {
            .nav-container {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background-color: var(--bg-color);
                box-shadow: var(--shadow);
                z-index: 100;
            }

            .nav-container.active {
                display: block;
            }

            .nav-menu {
                flex-direction: column;
                padding: 10px 0;
                width: 100%;
            }

            .nav-menu li {
                margin: 0;
                width: 100%;
            }

            .nav-menu a {
                padding: 12px 20px;
                border-radius: 0;
                width: 100%;
                display: block;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .user-menu-item {
                border-top: 1px solid var(--border-color);
                margin-top: 5px;
                padding-top: 5px;
            }
        }
    </style>

    <div class="header-container">
        <div class="logo">
            <img src="./assets/images/logo.png" alt="Logo Perpustakaan Digital">
            <h1>Perpustakaan Digital</h1>
        </div>

        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="nav-container" id="navContainer">
            <ul class="nav-menu" id="navMenu">
                <li><a href="./index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Beranda</a></li>
                <li><a href="./katalog.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'katalog.php' ? 'active' : ''; ?>">Katalog</a></li>
                <li><a href="./tentang.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tentang.php' ? 'active' : ''; ?>">Tentang</a></li>
                <li><a href="./kontak.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'kontak.php' ? 'active' : ''; ?>">Kontak</a></li>

                <!-- User menu items -->
                <?php if (isset($_SESSION['id_pengguna'])): ?>
                    <?php if ($_SESSION['peran_pengguna'] == 'admin'): ?>
                        <li class="user-menu-item"><a href="./admin/index.php">Panel Admin</a></li>
                    <?php else: ?>
                        <li class="user-menu-item"><a href="./dashboard-pengguna.php">Dashboard Saya</a></li>
                        <li class="user-menu-item"><a href="./keluar.php">Keluar</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="user-menu-item"><a href="./masuk.php">Masuk</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<script>
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('navContainer').classList.toggle('active');
    });
</script>