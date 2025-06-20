<?php
session_start();
include 'koneksi.php';

// Inisialisasi variabel sesi dengan aman
$user_id = $_SESSION['user_id'] ?? null;
$username_session = $_SESSION['username'] ?? null;
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Pengunjung';
$level = isset($_SESSION['level']) ? $_SESSION['level'] : 'guest';
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';

// ==================== PERBAIKAN HAK AKSES PENULIS ====================
// Ambil id author berdasarkan email session jika level penulis
$author_id = null;
if ($level === 'penulis' && isset($_SESSION['email'])) {
    $email_safe = mysqli_real_escape_string($conn, $_SESSION['email']);
    $q = mysqli_query($conn, "SELECT id FROM author WHERE email='$email_safe' LIMIT 1");
    if ($row = mysqli_fetch_assoc($q)) {
        $author_id = $row['id'];
    }
}

// Query utama untuk daftar artikel, mengambil author_ids untuk validasi hak akses
$query_main = "SELECT a.*, 
    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories, 
    GROUP_CONCAT(DISTINCT au.id SEPARATOR ',') as author_ids,
    GROUP_CONCAT(DISTINCT au.nickname SEPARATOR ', ') as authors
    FROM article a
    LEFT JOIN article_category ac ON a.id = ac.article_id
    LEFT JOIN category c ON ac.category_id = c.id
    LEFT JOIN article_author aa ON a.id = aa.article_id
    LEFT JOIN author au ON aa.author_id = au.id";

$where = [];
if (!empty($cari)) {
    $keywords = preg_split('/\\s+/', $cari);
    foreach ($keywords as $kw) {
        $kw = mysqli_real_escape_string($conn, $kw);
        $where[] = "(a.title LIKE '%$kw%' OR a.content LIKE '%$kw%' OR c.name LIKE '%$kw%' OR au.nickname LIKE '%$kw%')";
    }
}

// Jika yang login adalah Penulis DAN dia TIDAK sedang mencari, filter artikelnya sendiri
if ($level === 'penulis' && $author_id && empty($cari)) {
    $where[] = "a.id IN (SELECT article_id FROM article_author WHERE author_id = $author_id)";
}

if (!empty($where)) {
    $query_main .= " WHERE " . implode(" AND ", $where);
}

$query_main .= " GROUP BY a.id ORDER BY a.date DESC";

// Query untuk data sidebar
$query_sidebar_cat = "SELECT name, (SELECT COUNT(*) FROM article_category WHERE category_id=category.id) as post_count FROM category ORDER BY name ASC";
$result_sidebar_cat = mysqli_query($conn, $query_sidebar_cat);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles</title>
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
                <span class="ms-2 fw-bold" style="color:#f9d923;font-size:1.5rem;">Lapor Pak!</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="artikel.php"><i
                                class="bi bi-file-earmark-richtext-fill"></i> Articles</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="kategoriDropdown" role="button"
                            data-bs-toggle="dropdown"><i class="bi bi-bookmarks-fill"></i> Category</a>
                        <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                            <?php
                            mysqli_data_seek($result_sidebar_cat, 0); // Reset pointer query
                            while ($cat = mysqli_fetch_assoc($result_sidebar_cat)) {
                                echo '<li><a class="dropdown-item" href="kategori.php?kategori=' . urlencode($cat['name']) . '">' . htmlspecialchars($cat['name']) . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="penulis.php"><i
                                class="bi bi-file-earmark-person-fill"></i> Authors</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#tentangsaya"><i
                                class="bi bi-person-bounding-box"></i> About Me</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
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

    <div class="header" data-aos="fade-down">
        <h1>Semua Artikel di Blog Lapor Pak!</h1>
        <p class="subtitle">Jelajahi berbagai artikel menarik seputar perkembangan teknologi terkini, ulasan mendalam
            seputar pendidikan dan ilmu pengetahuan, hingga eksplorasi wisata, olahraga, dan kuliner Indonesia—semuanya
        </p>
    </div>

    <!-- ALERT CONTAINER UNTUK PESAN HAPUS -->
    <div class="container mt-3">
        <div id="alert-container"></div>
    </div>

    <main class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <?php if ($level == 'admin' || $level == 'penulis'): ?>
                    <div class="d-flex justify-content-end mb-4" data-aos="fade-up">
                        <a href="CRUD/Tambah.php?type=artikel" class="btn btn-success"><i class="bi bi-plus-circle"></i>
                            Tambah Artikel Baru</a>
                    </div>
                <?php endif; ?>

                <?php
                $main_result = mysqli_query($conn, $query_main);
                if ($main_result && mysqli_num_rows($main_result) > 0) {
                    while ($row = mysqli_fetch_assoc($main_result)) {
                        ?>
                        <article class="post" data-aos="fade-up">
                            <?php if (!empty($row['picture'])): ?>
                                <a href="detail.php?id=<?= $row['id'] ?>"><img src="images/<?= htmlspecialchars($row['picture']) ?>"
                                        class="post-img" alt="<?= htmlspecialchars($row['title']) ?>"></a>
                            <?php endif; ?>

                            <h2><a href="detail.php?id=<?= $row['id'] ?>" class="text-decoration-none"
                                    style="color: #F9D923"><?= htmlspecialchars($row['title']) ?></a></h2>

                            <p class="date">
                                <?= htmlspecialchars(date('d F Y', strtotime($row['date']))) ?>
                                <?php if (!empty($row['authors'])): ?>
                                    &bull; oleh <span class="author"><?= htmlspecialchars($row['authors']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($row['categories'])): ?>
                                    &bull; Kategori: <span class="category"><?= htmlspecialchars($row['categories']) ?></span>
                                <?php endif; ?>
                            </p>

                            <div class="post-content">
                                <?= mb_strimwidth(strip_tags($row['content']), 0, 250, '...') ?>
                            </div>

                            <a href="detail.php?id=<?= $row['id'] ?>" class="read-more-btn">Lanjutkan Membaca</a>

                            <?php
                            $author_ids_array = explode(',', $row['author_ids']);
                            if ($level == 'admin' || ($level == 'penulis' && in_array($author_id, $author_ids_array))):
                                ?>
                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary">
                                    <a href="CRUD/Edit.php?type=artikel&id=<?= $row['id'] ?>"
                                        class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <form method="POST" action="CRUD/Hapus.php" class="mb-0 delete-form">
                                        <input type="hidden" name="type" value="artikel">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i>
                                            Hapus</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </article>
                        <?php
                    } // Akhir while
                } else {
                    echo '<div class="post text-center" data-aos="fade-up"><h2 class="text-danger">Artikel Tidak Ditemukan</h2><p>Maaf, tidak ada artikel yang cocok dengan pencarian Anda atau yang Anda tulis.</p></div>';
                }
                ?>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div id="tentangsaya" class="post mb-4" data-aos="fade-left">
                        <h3 class="h5 p-3"><i class="bi bi-search me-2"></i>Search</h3>
                        <div class="p-3">
                            <form action="artikel.php" method="GET" class="d-flex">
                                <input type="text" name="cari" class="form-control me-2" placeholder="Cari..."
                                    style="background:rgb(255, 255, 255); color: #1e2746; border: 1px solid #0f3460;">
                                <button class="btn btn-primary" type="submit" title="Cari">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="artikel.php" class="btn btn-primary ms-2" title="Reset Pencarian">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </form>
                        </div>
                    </div>
                    <div id="tentangsaya" class="post text-center mb-4" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="h5 p-3"><i class="bi bi-bookmarks-fill me-2"></i>Kategori</h3>
                        <ul class="list-unstyled p-3 mb-0">
                            <?php
                            mysqli_data_seek($result_sidebar_cat, 0);
                            while ($cat = mysqli_fetch_assoc($result_sidebar_cat)) {
                                echo '<li class="mb-2"><a href="kategori.php?kategori=' . urlencode($cat['name']) . '" class="text-decoration-none d-flex justify-content-between nav-link"><span>' . htmlspecialchars($cat['name']) . '</span> <span>(' . $cat['post_count'] . ')</span></a></li>';
                            }
                            ?>
                        </ul>
                    </div>
                    <div id="tentangsaya" class="post text-center p-4" data-aos="fade-left" data-aos-delay="200">
                        <h3>Tentang</h3>
                        <p class="text-muted">Mengulas topik seputar teknologi, seperti AI dan inovasi digital; edukasi,
                            mulai dari pembelajaran hingga pengembangan diri; travel, dengan cerita dari berbagai
                            penjuru Indonesia; sport, yang menyoroti semangat dan prestasi atlet; serta kuliner, dari
                            makanan tradisional hingga tren kekinian.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            AOS.init({ duration: 800, once: true, offset: 50 });

            // Fitur hapus AJAX
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (confirm('Yakin ingin menghapus artikel ini?')) {
                        const formData = new FormData(form);
                        const postElement = form.closest('.post');
                        fetch(form.action, { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(data => {
                                // Tampilkan pesan ke alert-container
                                document.getElementById('alert-container').innerHTML = data.html;
                                if (data.status === 'success') {
                                    postElement.style.transition = 'opacity 0.5s, transform 0.5s';
                                    postElement.style.opacity = '0';
                                    setTimeout(() => postElement.remove(), 500);
                                }
                                // Pesan otomatis hilang setelah 3 detik
                                setTimeout(() => {
                                    document.getElementById('alert-container').innerHTML = '';
                                }, 3000);
                            })
                            .catch(() => {
                                document.getElementById('alert-container').innerHTML = '<div class="alert alert-danger">Terjadi kesalahan jaringan.</div>';
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>