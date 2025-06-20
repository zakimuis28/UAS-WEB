<?php
session_start();
include 'koneksi.php';

// Inisialisasi variabel sesi dengan aman
$user_id = $_SESSION['user_id'] ?? null;
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Pengunjung';
$level = isset($_SESSION['level']) ? $_SESSION['level'] : 'guest';

// Ambil nama kategori dari URL dan pastikan tidak kosong
$kategori_nama = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
if (empty($kategori_nama)) {
    header('Location: artikel.php');
    exit();
}
$kategori_nama_safe = mysqli_real_escape_string($conn, $kategori_nama);

// Query utama yang efisien untuk mengambil artikel berdasarkan nama kategori
$query_main = "SELECT a.*, 
    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories, 
    GROUP_CONCAT(DISTINCT au.id SEPARATOR ',') as author_ids, 
    GROUP_CONCAT(DISTINCT au.nickname SEPARATOR ', ') as authors
    FROM article a
    INNER JOIN article_category ac ON a.id = ac.article_id
    INNER JOIN category c ON ac.category_id = c.id
    LEFT JOIN article_author aa ON a.id = aa.article_id
    LEFT JOIN author au ON aa.author_id = au.id
    WHERE c.name = '$kategori_nama_safe'
    GROUP BY a.id 
    ORDER BY a.date DESC";
$main_result = mysqli_query($conn, $query_main);

// Query untuk mengisi widget sidebar kategori
$query_sidebar_cat = "SELECT name, (SELECT COUNT(*) FROM article_category WHERE category_id=category.id) as post_count FROM category ORDER BY name ASC";
$result_sidebar_cat = mysqli_query($conn, $query_sidebar_cat);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category</title>
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
                    <li class="nav-item"><a class="nav-link" href="artikel.php"><i
                                class="bi bi-file-earmark-richtext-fill"></i> Articles</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="kategoriDropdown" role="button"
                            data-bs-toggle="dropdown"><i class="bi bi-bookmarks-fill"></i> Category</a>
                        <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                            <?php
                            mysqli_data_seek($result_sidebar_cat, 0);
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="header" data-aos="fade-down">
        <p>Semua Artikel di Kategori</p>
        <h1><?= htmlspecialchars($kategori_nama) ?></h1>
    </div>

    <main class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <?php
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
                            </p>

                            <div class="post-content">
                                <?= mb_strimwidth(strip_tags($row['content']), 0, 250, '...') ?>
                            </div>

                            <a href="detail.php?id=<?= $row['id'] ?>" class="read-more-btn">Lanjutkan Membaca</a>

                            <?php
                            $author_ids_array = explode(',', $row['author_ids']);
                            if ($level == 'admin' || ($level == 'penulis' && in_array($user_id, $author_ids_array))):
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
                    echo '<div class="post text-center" data-aos="fade-up"><h2 class="text-danger">Artikel Tidak Ditemukan</h2><p>Maaf, belum ada artikel untuk kategori ini.</p></div>';
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
                                    style="background:rgb(255, 255, 255); color: #1e2746; border: none;">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                            </form>
                        </div>
                    </div>
                    <div id="tentangsaya" class="post mb-4" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="h5 p-3"><i class="bi bi-bookmarks-fill me-2"></i>Kategori Lain</h3>
                        <ul class="list-unstyled p-3 mb-0">
                            <?php
                            mysqli_data_seek($result_sidebar_cat, 0);
                            while ($cat = mysqli_fetch_assoc($result_sidebar_cat)) {
                                if ($cat['name'] !== $kategori_nama) {
                                    echo '<li class="mb-2"><a href="kategori.php?kategori=' . urlencode($cat['name']) . '" class="text-decoration-none d-flex justify-content-between nav-link"><span>' . htmlspecialchars($cat['name']) . '</span> <span>(' . $cat['post_count'] . ')</span></a></li>';
                                }
                            }
                            ?>
                        </ul>
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
            // Script AJAX untuk hapus dinamis akan berfungsi di sini jika diperlukan
        });
    </script>
</body>

</html>