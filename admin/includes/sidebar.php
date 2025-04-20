<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2>Panel Admin</h2>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 576 512">
                    <path d="M288 32C129.9 32 0 161.9 0 320c0 38.4 7.9 74.9 22.1 108.1 5.6 13 19.1 21.2 33.7 21.2h107.6c15.5 0 29.2-10.1 33.5-25.1l15.1-52.8c4.3-15 18-25.3 33.5-25.3h72.4c15.5 0 29.2 10.3 33.5 25.3l15.1 52.8c4.3 15 18 25.1 33.5 25.1h107.6c14.6 0 28.1-8.2 33.7-21.2C568.1 394.9 576 358.4 576 320 576 161.9 446.1 32 288 32zm-48 240c0-17.7 14.3-32 32-32s32 14.3 32 32-14.3 32-32 32-32-14.3-32-32z" />
                </svg>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="buku.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'buku.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 448 512">
                    <path d="M96 0C60.7 0 32 28.7 32 64V448c0 35.3 28.7 64 64 64h304c8.8 0 16-7.2 16-16V432c0-8.8-7.2-16-16-16H128c-17.7 0-32-14.3-32-32s14.3-32 32-32h272c8.8 0 16-7.2 16-16V64c0-35.3-28.7-64-64-64H96z" />
                </svg>
                <span>Buku</span>
            </a>
        </li>
        <li>
            <a href="kategori.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M501.5 234.3 278.6 11.4C270.8 3.6 260.1 0 249.1 0H48C21.5 0 0 21.5 0 48v201.1c0 11 4.6 21.6 12.4 29.4l222.9 222.9c8.1 8.1 19 12.6 30.6 12.6s22.5-4.5 30.6-12.6L501.5 295.5c8.1-8.1 12.5-18.8 12.5-30.2s-4.4-22.1-12.5-30.2zM112 144c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32z" />
                </svg>
                <span>Kategori</span>
            </a>
        </li>
        <li>
            <a href="anggota.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'anggota.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 448 512">
                    <path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm89.6 32h-11.7c-22.2 10.3-46.9 16-73.9 16s-51.7-5.7-73.9-16h-11.7C65.3 288 0 353.3 0 432v16c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64v-16c0-78.7-65.3-144-134.4-144z" />
                </svg>
                <span>Anggota</span>
            </a>
        </li>
        <li>
            <a href="peminjaman.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'peminjaman.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M0 168c0-13.3 10.7-24 24-24H393.4l-39.7-39.7c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39.7-39.7H24c-13.3 0-24-10.7-24-24zm512 176c0 13.3-10.7 24-24 24H118.6l39.7 39.7c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-80-80c-9.4-9.4-9.4-24.6 0-33.9l80-80c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-39.7 39.7H488c13.3 0 24 10.7 24 24z" />
                </svg>
                <span>Peminjaman</span>
            </a>
        </li>
        <li>
            <a href="denda.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'denda.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M256 8C119.043 8 8 119.043 8 256s111.043 248 248 248 248-111.043 248-248S392.957 8 256 8zm0 110c13.255 0 24 10.745 24 24v120c0 13.255-10.745 24-24 24s-24-10.745-24-24V142c0-13.255 10.745-24 24-24zm0 272c-17.673 0-32-14.327-32-32s14.327-32 32-32 32 14.327 32 32-14.327 32-32 32z" />
                </svg>
                <span>Denda</span>
            </a>
        </li>
        <li>
            <a href="laporan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M500 384c6.627 0 12 5.373 12 12v36c0 13.255-10.745 24-24 24H24c-13.255 
                0-24-10.745-24-24v-36c0-6.627 5.373-12 12-12h464zm-372-64c6.627 0 
                12 5.373 12 12v76c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-76c0-6.627 
                5.373-12 12-12h40zm96-96c6.627 0 12 5.373 12 12v172c0 6.627-5.373 
                12-12 12h-40c-6.627 0-12-5.373-12-12V236c0-6.627 5.373-12 12-12h40zm96-64c6.627 0 
                12 5.373 12 12v236c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12V172c0-6.627 
                5.373-12 12-12h40zm96-64c6.627 0 12 5.373 12 12v300c0 6.627-5.373 
                12-12 12h-40c-6.627 0-12-5.373-12-12V140c0-6.627 5.373-12 12-12h40z" />
                </svg>
                <span>Laporan</span>
            </a>
        </li>
        <li>
            <a href="pengaturan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pengaturan.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12a7.5 7.5 0 00-.127-1.368l1.509-1.174a.75.75 0 00.179-.942l-1.5-2.598a.75.75 0 00-.928-.323l-1.787.715a7.512 7.512 0 00-2.362-1.368l-.27-1.897A.75.75 0 0013.5 2h-3a.75.75 0 00-.745.645l-.27 1.897a7.512 7.512 0 00-2.362 1.368l-1.787-.715a.75.75 0 00-.928.323l-1.5 2.598a.75.75 0 00.179.942l1.509 1.174A7.501 7.501 0 004.5 12c0 .468.042.925.127 1.368l-1.509 1.174a.75.75 0 00-.179.942l1.5 2.598a.75.75 0 00.928.323l1.787-.715a7.512 7.512 0 002.362 1.368l.27 1.897a.75.75 0 00.745.645h3a.75.75 0 00.745-.645l.27-1.897a7.512 7.512 0 002.362-1.368l1.787.715a.75.75 0 00.928-.323l1.5-2.598a.75.75 0 00-.179-.942l-1.509-1.174c.085-.443.127-.9.127-1.368z" />
                </svg>
                <span>Pengaturan</span>
            </a>
        </li>
        <li>
            <a href="../keluar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'keluar.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3H6.75A2.25 2.25 0 004.5 5.25v13.5A2.25 2.25 0 006.75 21H13.5a2.25 2.25 0 002.25-2.25V15" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m0 0l3-3m-3 3l3 3" />
                </svg>
                <span>Keluar Akun</span>
            </a>
        </li>
    </ul>
</div>