<header>
    <style>
        @media (min-width: 769px) {
            .mobile-toggle {
                display: none;
            }
        }
    </style>
    <div class="header-container">
        <div class="logo">
            <img src="../assets/images/logo.png" alt="Logo Perpustakaan Digital">
            <h1>Panel Admin</h1>
        </div>

        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="nav-container" id="navContainer">
            <ul class="nav-menu" id="navMenu">
                <li class="user-menu-item"><a href="../index.php">Lihat Situs</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="mobile-toggle" id="sidebarToggle">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
        <path d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</div>

<script>
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('navContainer').classList.toggle('active');
    });

    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('active');
    });
</script>