<?php
session_start();
include 'koneksi.php';

// Cek apakah pengguna sudah login atau belum untuk menentukan tampilan
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['level']);
$level = $_SESSION['level'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? null;

if ($is_logged_in) {
    // ==========================================================
    // BAGIAN LOGIKA UNTUK TAMPILAN DASHBOARD (JIKA SUDAH LOGIN)
    // ==========================================================
    $email = htmlspecialchars($_SESSION['email']);
    $namalengkap = htmlspecialchars($_SESSION['namalengkap']);

    // Query untuk statistik dashboard
    $total_artikel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM article"))['total'];
    $total_kategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM category"))['total'];
    $total_penulis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM author"))['total'];

} else {
    // ==========================================================
    // BAGIAN LOGIKA UNTUK TAMPILAN HOMEPAGE (JIKA PENGUNJUNG)
    // ==========================================================

    // Query untuk mengambil 7 artikel terbaru di kolom kiri
    $query_artikel_terbaru = "SELECT a.*, 
        GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories, 
        GROUP_CONCAT(DISTINCT au.nickname SEPARATOR ', ') as authors
        FROM article a
        LEFT JOIN article_category ac ON a.id = ac.article_id
        LEFT JOIN category c ON ac.category_id = c.id
        LEFT JOIN article_author aa ON a.id = aa.article_id
        LEFT JOIN author au ON aa.author_id = au.id
        GROUP BY a.id 
        ORDER BY a.date DESC LIMIT 7";
    $result_artikel_terbaru = mysqli_query($conn, $query_artikel_terbaru);

    // Query untuk mengisi widget sidebar
    $query_sidebar_cat = "SELECT name, (SELECT COUNT(*) FROM article_category WHERE category_id=category.id) as post_count FROM category ORDER BY name ASC";
    $result_sidebar_cat = mysqli_query($conn, $query_sidebar_cat);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Lapor Pak!</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@400;500&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Montserrat', Arial, sans-serif;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" data-aos="fade-down">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="images/Logo2.png" alt="Logo">
                <span class="ms-2 fw-bold" style="color:#f9d923;font-size:1.5rem;">
                    <?= $is_logged_in ? 'Dashboard' : "Lapak Pak!" ?>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-house"></i>
                            Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="artikel.php"><i
                                class="bi bi-file-earmark-richtext-fill"></i> Articles</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="kategoriDropdown" role="button"
                            data-bs-toggle="dropdown"><i class="bi bi-bookmarks-fill"></i> Category</a>
                        <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                            <?php
                            $nav_cat_res = mysqli_query($conn, "SELECT name FROM category ORDER BY name ASC");
                            while ($cat = mysqli_fetch_assoc($nav_cat_res)) {
                                echo '<li><a class="dropdown-item" href="kategori.php?kategori=' . urlencode($cat['name']) . '">' . htmlspecialchars($cat['name']) . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="penulis.php"><i
                                class="bi bi-file-earmark-person-fill"></i> Authors</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentangsaya"> About Me</a></li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i>
                                Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i>
                                Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($is_logged_in): ?>

        <div class="header" data-aos="fade-down">
            <h1>Selamat Datang, <?= $namalengkap ?>!</h1>
            <p class="lead">
                <?php if ($level == 'admin'): ?>
                    Ini adalah dashboard admin untuk mengelola artikel, kategori, dan penulis.
                <?php elseif ($level == 'penulis'): ?>
                    Ini adalah dashboard penulis untuk mengelola artikel, kategori, dan penulis.
                <?php else: ?>
                    Ini adalah dashboard Pengunjung.
                <?php endif; ?>
            </p>
        </div>
        <main class="container py-5">
            <div id="jumlah" class="p-4 p-md-5 text-center mb-5" data-aos="fade-up">
                <div class="row justify-content-center g-4">
                    <div class="col-md-3">
                        <h5 class="card-title">Jumlah Artikel</h5>
                        <p class="card-text display-4 fw-bold"><?= $total_artikel ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5 class="card-title">Jumlah Kategori</h5>
                        <p class="card-text display-4 fw-bold"><?= $total_kategori ?></p>
                    </div>
                    <div class="col-md-3">
                        <h5 class="card-title">Jumlah Penulis</h5>
                        <p class="card-text display-4 fw-bold"><?= $total_penulis ?></p>
                    </div>
                </div>
            </div>
            <div class="row" data-aos="fade-up" data-aos-delay="100">
                <div class="col-lg-10 mx-auto">
                    <div class="post p-3">
                        <h5 class="text-center mb-3">Statistik Konten</h5>
                        <div class="card-body">
                            <canvas id="artikelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    <?php else: ?>

        <div class="header" data-aos="fade-down">
            <h1>Selamat Datang! Pengunjung</h1>
            <p class="subtitle">Jelajahi berbagai artikel menarik seputar teknologi.</p>
        </div>
        <main class="container py-5">
            <div class="row g-5">
                <div class="col-lg-8">
                    <h2 class="mb-4" data-aos="fade-up">Artikel Terbaru</h2>
                    <?php
                    if ($result_artikel_terbaru && mysqli_num_rows($result_artikel_terbaru) > 0):
                        while ($row = mysqli_fetch_assoc($result_artikel_terbaru)):
                            ?>
                            <article class="post" data-aos="fade-up">
                                <?php if (!empty($row['picture'])): ?>
                                    <a href="detail.php?id=<?= $row['id'] ?>"><img src="images/<?= htmlspecialchars($row['picture']) ?>"
                                            class="post-img" alt="<?= htmlspecialchars($row['title']) ?>"></a>
                                <?php endif; ?>
                                <h2><a href="detail.php?id=<?= $row['id'] ?>"
                                        class="text-decoration-none"><?= htmlspecialchars($row['title']) ?></a></h2>
                                <p class="date">
                                    <?= htmlspecialchars(date('d F Y', strtotime($row['date']))) ?>
                                    <?php if (!empty($row['authors'])): ?>
                                        &bull; oleh <span class="author"><?= htmlspecialchars($row['authors']) ?></span>
                                    <?php endif; ?>
                                </p>
                                <div class="post-content">
                                    <?= mb_strimwidth(strip_tags($row['content']), 0, 250, '...') ?>
                                </div>
                                <a href="detail.php?id=<?= $row['id'] ?>" class="read-more-btn">Lanjutkan Membaca</a>
                            </article>
                        <?php
                        endwhile;
                    else:
                        echo '<div class="post text-center"><h2 class="text-danger">Belum Ada Artikel</h2><p>Saat ini belum ada artikel yang dipublikasikan.</p></div>';
                    endif;
                    ?>
                </div>

                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 100px;">
                        <div id="tentangsaya" class="post text-center mb-4" data-aos="fade-left">
                            <h3 class="h5 p-3"><i class="bi bi-search me-2"></i>Search</h3>
                            <div class="p-3">
                                <form action="artikel.php" method="GET" class="d-flex">
                                    <input type="text" name="cari" class="form-control me-2" placeholder="Cari..."
                                        style="background:rgb(255, 255, 255); color: #1e2746; border: none;">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                </form>
                            </div>
                        </div>
                        <div id="tentangsaya" class="post text-center mb-4" data-aos="fade-left" data-aos-delay="100">
                            <h3 class="h5 p-3"><i class="bi bi-bookmarks-fill me-2"></i>Kategori</h3>
                            <ul class="list-unstyled p-3 mb-0">
                                <?php mysqli_data_seek($result_sidebar_cat, 0);
                                while ($cat = mysqli_fetch_assoc($result_sidebar_cat)): ?>
                                    <li class="mb-2"><a href="kategori.php?kategori=<?= urlencode($cat['name']) ?>"
                                            class="text-decoration-none d-flex justify-content-between nav-link"><span><?= htmlspecialchars($cat['name']) ?></span>
                                            <span>(<?= $cat['post_count'] ?>)</span></a></li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div id="tentangsaya" class="post text-center p-4" data-aos="fade-left" data-aos-delay="200">
                            <h3>Tentang</h3>
                            <p class="text-muted"><b>Blog Lapor Pak!</b> Merupakan ruang berbagi cerita dan informasi
                                seputar teknologi, pendidikan, wisata, olahraga, dan kuliner. Lapor Pak! hadir untuk
                                menyampaikan pengetahuan dan pengalaman secara ringan, mudah dipahami, dan bermakna—tanpa
                                batasan wilayah, karena setiap tempat dan topik layak untuk diceritakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    <?php endif; ?>
    <!-- About Me -->
    <section id="tentangsaya" class="py-5" data-aos="fade-up">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 text-center mb-4 mb-md-0">
                    <img src="images/profil2.jpg" alt="Foto Profil" class="rounded-circle shadow"
                        style="width: 240px; height:240px; object-fit:cover;">
                </div>
                <div class="col-md-7">
                    <h2 class="fw-bold" style="color:#f9d923;">ABOUT ME</h2>
                    <p>Halo! Perkenalkan, saya <strong class="lead mb-2" style="color:#f9d923;">Muhammad Zaki
                            Mu'is</strong>, mahasiswa semester 4 Program Studi Teknik Informatika Universitas Islam
                        Negeri Maulana Malik Ibrahim Malang. </p>
                    <p><b>Keahlian:</b> PHP, MySQL, Bootstrap, JavaScript, UI/UX, Animasi Web.</p>
                    <p><b>Kontak Us:</b> +62 8953 2811 9977</b></p>
                    <div class="mb-2">
                        <a href="mailto:emailkamu@email.com" class="btn btn-outline-warning btn-sm me-2">Email</a>
                        <a href="https://www.instagram.com/zaki.muis?igsh=MXMxNGdqZDdkcGx2ZA==x"
                            class="btn btn-outline-danger btn-sm me-2" target="_blank">Instagram</a>
                        <a href="https://github.com/zakimuis28" class="btn btn-outline-dark btn-sm"
                            target="_blank">GitHub</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="footer text-center py-4" data-aos="fade-up">
        <strong class="text-warning">Logged in as:</strong>
        <span class="text-white">
            <?php if (isset($_SESSION['email'])): ?>
                <span class="text-white"><?php echo htmlspecialchars($_SESSION['email']); ?> </span>
            <?php else: ?>
                <span class="text-white">Pengunjung</span>
            <?php endif; ?>
        </span>
        <p class="text-muted mb-0"><i class="bi bi-person-raised-hand"></i> &copy; 2025 Blog Lapor Pak! | All rights
            reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <?php if ($is_logged_in): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('artikelChart').getContext('2d');
            const artikelChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Artikel', 'Kategori', 'Penulis'],
                    datasets: [{
                        label: 'Total Aset Konten',
                        data: [<?= $total_artikel ?>, <?= $total_kategori ?>, <?= $total_penulis ?>],
                        backgroundColor: ['#f9d923', '#e94560', '#16213e'],
                        borderColor: '#232946',
                        borderWidth: 2
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        </script>
    <?php endif; ?>

    <script>
        AOS.init({ duration: 800, once: true, offset: 50 });
    </script>
</body>

</html>