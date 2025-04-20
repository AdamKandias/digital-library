-- Skema database untuk Perpustakaan Digital

-- Membuat database
CREATE DATABASE IF NOT EXISTS perpustakaan_digital;
USE perpustakaan_digital;

-- Tabel kategori
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    ikon VARCHAR(255),
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel buku
CREATE TABLE buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    penulis VARCHAR(255) NOT NULL,
    penerbit VARCHAR(255),
    tahun_terbit INT,
    id_kategori INT,
    deskripsi TEXT,
    sampul VARCHAR(255),
    stok INT DEFAULT 1,
    tanggal_ditambahkan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id)
);

-- Tabel gambar buku
CREATE TABLE gambar_buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_buku INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    urutan INT DEFAULT 0,
    tanggal_ditambahkan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_buku) REFERENCES buku(id) ON DELETE CASCADE
);

-- Tabel pengguna
CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    peran ENUM('admin', 'anggota') DEFAULT 'anggota',
    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel peminjaman
CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna INT NOT NULL,
    id_buku INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_jatuh_tempo DATE NOT NULL,
    tanggal_kembali DATE,
    status ENUM('dipinjam', 'dikembalikan', 'hilang') DEFAULT 'dipinjam',
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id),
    FOREIGN KEY (id_buku) REFERENCES buku(id)
);

-- Tabel denda
CREATE TABLE denda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    id_pengguna INT NOT NULL,
    jumlah DECIMAL(10, 2) NOT NULL,
    status ENUM('dibayar', 'belum_dibayar') DEFAULT 'belum_dibayar',
    tanggal_dibayar DATETIME,
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id),
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id)
);

-- Tabel pengaturan
CREATE TABLE pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL UNIQUE,
    nilai VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    tanggal_diperbarui TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Memasukkan admin default (password: admin123)
INSERT INTO pengguna (nama, email, password, peran) VALUES 
('Admin', 'admin@example.com', '$2y$10$xRz.YTWGXOuIaqRR6tO26O.OhA/H.SE2HuAGq2q7aY0NZthfQtMd2', 'admin');

-- Memasukkan kategori contoh
INSERT INTO kategori (nama, ikon) VALUES 
('Fiksi', 'fiksi.png'),
('Non-Fiksi', 'non-fiksi.png'),
('Sains', 'sains.png'),
('Sejarah', 'sejarah.png'),
('Teknologi', 'teknologi.png'),
('Seni', 'seni.png');

-- Memasukkan buku contoh
INSERT INTO buku (judul, penulis, penerbit, tahun_terbit, id_kategori, deskripsi, stok, sampul) VALUES 
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 1, 'Laskar Pelangi adalah novel yang pertama kali diterbitkan oleh penulis kenamaan, Andrea Hirata. Tepatnya, novel ini berhasil dirilis pada tahun 2005 oleh Penerbit Bentang Pustaka. Dalam peradabannya, Andrea Hirata pun mengeluarkan tiga novel sekuel lanjutan dari Laskar Pelangi, di antaranya Sang Pemimpi, Edensor, dan Maryamah Karpov.
Laskar Pelangi merupakan novel yang terinspirasi dari kisah nyata kehidupan Andrea Hirata selaku penulis yang mana saat itu dirinya bertempat tinggal di Desa Gantung, Kabupaten Gantung, Belitung Timur. Berkenaan dengan hal tersebut, mudah bagi si penulis merepresentasikan berbagai unsur sosial dan budaya masyarakat Belitung ke dalam bentuk cerita di novel Laskar Pelangi ini secara apik.
"Bangunan itu nyaris rubuh. Dindingnya miring bersangga sebalok kayu. Atapnya bocor dimana-mana. Tetapi, berpasang-pasang mata mungil menatap penuh harap. Hendak kemana lagikah mereka harus bersekolah selain tempat itu? Tak peduli seberat apapun kondisi sekolah itu, sepuluh anak dari keluarga miskin itu tetap bergeming. Didada mereka, telah menggumpal tekad untuk maju."
Laskar Pelangi, kisah perjuangan anak-anak untuk mendapatkan ilmu. Diceritakan dengan lucu dan menggelitik, novel ini menjadi novel terlaris di Indonesia. Inspiratif dan layak dimiliki siapa saja yang mencintai pendidikan dan keajaiban masa kanak-kanak.', 5, 'laskar-pelangi.jpg'),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 1, 'Buku ini bercerita tentang perjalanan seorang tokoh bernama Minke. Minke adalah salah satu anak pribumi yang sekolah di HBS. Pada masa itu, yang dapat masuk ke sekolah HBS adalah orang-orang keturunan Eropa. Minke adalah seorang pribumi yang pandai, ia sangat pandai menulis. Tulisannya bisa membuat orang sampai terkagum-kagum dan dimuat di berbagai Koran Belanda pada saat itu. Sebagai seorang pribumi, ia kurang disukai oleh siswa-siswi Eropa lainnya. Minke digambarkan sebagai seorang revolusioner di buku ini. Ia berani melawan ketidakadilan yang terjadi pada bangsanya. Ia juga berani memberontak terhadap kebudayaan Jawa, yang membuatnya selalu di bawah.

Selain tokoh Minke, buku ini juga menggambarkan seorang "Nyai" yang bernama Nyai Ontosoroh. Nyai pada saat itu dianggap sebagai perempuan yang tidak memiliki norma kesusilaan karena statusnya sebagai istri simpanan. Statusnya sebagai seorang Nyai telah membuatnya sangat menderita, karena ia tidak memiliki hak asasi manusia yang sepantasnya. Tetapi, yang menariknya adalah Nyai Ontosoroh sadar akan kondisi tersebut sehingga dia berusaha keras dengan terus-menerus belajar, agar dapat diakui sebagai seorang manusia. Nyai Ontosoroh berpendapat, untuk melawan penghinaan, kebodohan, kemiskinan, dan sebagainya hanyalah dengan belajar. Minke juga menjalin asmara dan akhirnya menikah dengan Annelies, anak dari Nyai Ontosoroh dan tuan Mellema.

Melalui buku ini, Pram menggambarkan bagaimana keadaan pemerintahan kolonialisme Belanda pada saat itu secara hidup. Pram, menunjukan betapa pentingnya belajar. Dengan belajar, dapat mengubah nasib. Seperti di dalam buku ini, Nyai yang tidak bersekolah, dapat menjadi seorang guru yang hebat bagi siswa HBS dan Minke. Bahkan pengetahuan si nyai itu, yang didapat dari pengalaman, dari buku-buku, dan dari kehidupan sehari-hari, ternyata lebih luas dari guru-guru sekolah HBS.', 3, 'bumi-manusia.jpg'),
('Sejarah Singkat Waktu', 'Stephen Hawking', 'Bantam Dell', 1988, 3, 'Buku A Brief History of Time, Sejarah Singkat Waktu berisi pemahaman luas tentang alam semesta. Buku ini terdiri dari 12 bab yang setiap bab berkaitan satu dengan yang lainnya. Sekilas tentang isi pembahasan yang ada di buku ini, pada bab pertama, pembaca akan disajikan dengan pengetahuan tentang alam semesta. Bab kedua berisi tentang gambaran ruang dan waktu. Bab ketiga merupakan bab yang membahas tentang jawaban dari pertanyaan “Apakah alam semesta memiliki ujung?”. Pertanyaan ini dijawab dengan teori big bang. Buku ini cocok untuk pembaca yang menyukai dan ingin menambah pengetahuan tentang sains. "Inilah salah satu buku sains terpenting yang ditulis oleh satu di antara para ilmuwan besar zaman kita, Stephen Hawking. Dalam buku ini Hawking membahas pertanyaan-pertanyaan besar seperti: Bagaimana alam semesta bermula—dan apa yang memulainya? Apakah waktu itu, dan apakah ia selalu bergerak maju? Adakah ujung alam semesta, dalam ruang maupun waktu? Adakah dimensi lain dalam alam semesta? Apa yang terjadi ketika alam semesta berakhir?

Lewat penulisan yang bisa dimengerti semua orang, A Brief History of Time mengajak kita menjelajahi dunia ajaib lubang hitam dan kuark, antizat dan “panah waktu”, ledakan besar dan peran Tuhan di alam semesta beserta segala kemungkinan yang luar biasa dan tak terduga. Dengan penggambaran yang menarik dan menggugah imajinasi, Stephen Hawking membawa kita makin dekat ke rahasia pamungkas penciptaan alam semesta."', 2, 'sejarah-singkat-waktu.jpg'),
('Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 2, 'Kutipan dari Buku: “Kita memiliki kebiasaan membesar-besarkan kesedihan. Kita tercabik di antara hal-hal masa kini dan hal-hal yang baru terjadi. Pikirlah apakah sudah ada bukti yang pasti mengenai kesusahan masa depan. Karena sering kali kita lebih disusahkan kekhawatiran kita sendiri.” – Seneca

Buku ini pada awalnya menceritakan tentang sebuah survei kekhawatiran nasional yang semakin masif sekaligus menyajikan tentang sekilas kehidupan si penulis yang dipenuhi oleh emosi negatif yang berlebihan. Lalu, lebih dari 2000 tahun lalu sebuah mazhab filsafat menemukan akar masalah dan solusi dari banyaknya emosi negatif. Ya, Stoisisme atau
filosofi Stoa, namun penulis lebih memperkenalkannya dengan “Filosofi Teras” yang merupakan filsafat Yunani-Romawi Kuno yang dapat membantu kita dalam mengatasi emosi negatif serta menghasilkan mental seseorang menjadi tangguh dalam menghadapi naik turunnya kehidupan. Dalam buku tersebut, filsafat Stoa digambarkan secara sederhana dengan inti dikotomi kendali nasib manusia sehingga dari dikotomi kendali tersebut, manusia dapat menentukan hal-hal yang dapat membuatnya bahagia maupun tidak. Namun, Wiliam Irvine menawarkan trikotomi kendali di mana memuat apa yang menjadi kendali kita, tidak
menjadi kendali kita, dan juga menjadi bagian dari kendali kita.

Buku Filosofi Teras ini sangat berbeda dengan buku filsafat lainnya karena filosofi teras (Stoa) digambarkan dengan analogi kejadian yang real di kehidupan sehari-hari dan penggunanan bahasa yang sesuai dengan Generasi Milenial dan Gen-Z. Hal yang menarik dari Filosofi Teras ini terletak pada tujuannya yaitu hidup dalam ketenangan dan terbebas dari emosi negatif. Oleh karena itu, pada setiap bab Filososfi Teras terdapat pelajaran yang diambil, salah satunya yaitu dalam menjalani kehidupan harus selaras dengan alam. Di mana kehidupan berjalan sesuai kehendak pencipta-Nya dan selaras dengan alam itu berarti kita harus mengandalkan akal kita agar tidak terbawa arus yang menyimpang. Apalagi sekarang ini banyak di antara kita yang menggunakan medsos dan sering ditemui berita hoaks, sehingga kita tidak boleh terbawa emosi dan tidak baperan. Satu hal yang haru kita ingat, jangan terlalu memikirkan hal yang belum terjadi ke depannya, biarkan berjalan sebagaimana mestinya, namun tetap diiringi dengan effort supaya mendapat hasil yang maksimal.', 4, 'filosofi-teras.jpg'),
('Pemrograman Web dengan PHP dan MySQL', 'Betha Sidik', 'Informatika', 2017, 5, 'Buku Pemrograman Web dengan PHP (Revisi Kedua) ini lebih memfokuskan kepada pembahasan dengan menggunakan PHP5, dengan menggunakan versi PHP5.3.38 sebagai versi pengujiannya . Buku ini disusun dengan materi awal masih menggunakan susunan pembahasan dari buku sebelumnya, karena penulis masih mempertahankan pemrograman PHP secara bertahap dari dasar. Dalam buku ini ditambahkan dan ditunjukkan beberapa cara untuk memasang PHP pada beberapa server web yang populer saat ini, IIS sebagai sertaan dari Windows, Apache sebagai server web open source awal, Lighttpd ringan, dan Nginx yang diaku sebagai server web paling ringan. Beberapa materi telah ditambah dan diperbarui informasinya, sesuai dengan perkembangan terakhir, seperti MySQL yang telah dibeli oleh Oracle dan MariaDB sebagai database alternatif MySQL yang opensource. Dalam buku ini juga dikenalkan tentang JSON dan database NoSQL seperti MongoDB, sebagai alternatif dari sistem database yang bisa digunakan untuk pembuatan aplikasi. 
Buku Pemrograman Web dengan PHP (Revisi Kedua) ini lebih memfokuskan kepada pembahasan dengan menggunakan PHP5, dengan menggunakan versi PHP5.3.38 sebagai versi pengujiannya . Buku ini disusun dengan materi awal masih menggunakan susunan pembahasan dari buku sebelumnya, karena penulis masih mempertahankan pemrograman PHP secara bertahap dari dasar.

Dalam buku ini ditambahkan dan ditunjukkan beberapa cara untuk memasang PHP pada beberapa server web yang populer saat ini, IIS sebagai sertaan dari Windows, Apache sebagai server web open source awal, Lighttpd ringan, dan Nginx yang diaku sebagai server web paling ringan.

Beberapa materi telah ditambah dan diperbarui informasinya, sesuai dengan perkembangan terakhir, seperti MySQL yang telah dibeli oleh Oracle dan MariaDB sebagai database alternatif MySQL yang opensource. Dalam buku ini juga dikenalkan tentang JSON dan database NoSQL seperti MongoDB, sebagai alternatif dari sistem database yang bisa digunakan untuk pembuatan aplikasi.', 3, 'pemrograman-web.jpg'),
('The Story Of Art', 'Ernst Gombrich', 'Phaidon', 1950, 6, 'The Story of Art oleh Ernst H. Gombrich adalah salah satu karya paling ikonik dan berpengaruh dalam sejarah literatur seni rupa. Pertama kali diterbitkan pada tahun 1950, buku ini telah menjadi pengantar utama bagi jutaan pembaca di seluruh dunia untuk memahami dan mengapresiasi seni dari berbagai era dan budaya. Ditulis dengan gaya yang jernih, hangat, dan komunikatif, Gombrich berhasil menyampaikan kompleksitas sejarah seni dengan cara yang mudah diakses oleh pembaca umum tanpa mengorbankan kedalaman intelektualnya.

Dalam buku ini, Gombrich membawa pembaca melalui perjalanan waktu yang luar biasa—dari seni prasejarah dan peradaban Mesir kuno, hingga karya-karya agung dari Renaisans, Barok, hingga seni modern abad ke-20. Alih-alih hanya menyajikan data atau teori kaku, Gombrich menekankan bahwa seni adalah cerminan dari pengalaman manusia; bagaimana seniman menanggapi dunia di sekitar mereka, bagaimana teknik dan gaya berkembang, dan bagaimana makna visual dibentuk oleh konteks budaya dan sejarah.

Salah satu kekuatan utama buku ini adalah pendekatannya yang naratif. Gombrich tidak sekadar mengajarkan seni, ia menceritakan seni sebagai kisah yang hidup—penuh perubahan, penemuan, dan semangat manusia. Ia juga dikenal karena pendekatannya yang inklusif, membahas berbagai bentuk seni dengan penghargaan yang sama, dan menekankan pentingnya memahami maksud dan latar belakang dari tiap karya yang dibahas.

Buku ini bukan hanya dilengkapi dengan ratusan ilustrasi berkualitas tinggi, tetapi juga dengan analisis yang mendalam dan penuh wawasan terhadap teknik, gaya, dan dampak karya seni. Lebih dari sekadar buku teks, The Story of Art adalah sebuah jembatan antara dunia akademik dan publik umum—membuka pintu bagi siapa saja yang ingin mengenal seni lebih dalam, baik pelajar, pengajar, maupun pecinta seni biasa', 1, 'the-story-of-art.jpg');

-- Memasukkan gambar buku contoh
INSERT INTO gambar_buku (id_buku, nama_file, urutan) VALUES 
(1, 'laskar-pelangi-1.jpg', 1),
(1, 'laskar-pelangi-2.jpg', 2),
(1, 'laskar-pelangi-3.jpg', 3),
(2, 'bumi-manusia-1.jpg', 1),
(2, 'bumi-manusia-2.jpg', 2),
(3, 'sejarah-singkat-waktu-1.jpg', 1),
(3, 'sejarah-singkat-waktu-2.jpg', 2),
(4, 'filosofi-teras-1.jpg', 1),
(4, 'filosofi-teras-2.jpg', 2),
(5, 'pemrograman-web-1.jpg', 1),
(5, 'pemrograman-web-2.jpg', 2),
(6, 'the-story-of-art-1.jpg', 1),
(6, 'the-story-of-art-2.jpg', 2);

-- Memasukkan pengaturan default
INSERT INTO pengaturan (nama, nilai, deskripsi) VALUES
('durasi_peminjaman', '14', 'Durasi peminjaman buku dalam hari'),
('denda_per_hari', '10000', 'Jumlah denda per hari keterlambatan (dalam Rupiah)'),
('denda_buku_hilang', '100000', 'Jumlah denda untuk buku yang hilang (dalam Rupiah)'),
('max_peminjaman', '3', 'Jumlah maksimum buku yang dapat dipinjam oleh satu anggota'),