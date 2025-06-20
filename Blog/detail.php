<?php
session_start();
include 'koneksi.php';

// Inisialisasi variabel sesi dengan aman
$user_id = $_SESSION['user_id'] ?? null;
$level = $_SESSION['level'] ?? 'guest';
$is_logged_in = (bool) $user_id;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: artikel.php');
    exit;
}

// Optimasi Performa: Mengambil semua data dalam satu query efisien
$query_artikel = "SELECT a.*, 
                    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories, 
                    GROUP_CONCAT(DISTINCT c.id SEPARATOR ',') as category_ids,
                    GROUP_CONCAT(DISTINCT au.id SEPARATOR ',') as author_ids,
                    GROUP_CONCAT(DISTINCT au.nickname SEPARATOR ', ') as authors
                  FROM article a
                  LEFT JOIN article_category ac ON a.id = ac.article_id
                  LEFT JOIN category c ON ac.category_id = c.id
                  LEFT JOIN article_author aa ON a.id = aa.article_id
                  LEFT JOIN author au ON aa.author_id = au.id
                  WHERE a.id = $id
                  GROUP BY a.id";
$result_artikel = mysqli_query($conn, $query_artikel);
$artikel = mysqli_fetch_assoc($result_artikel);

// Jika artikel tidak ditemukan, tampilkan halaman error yang sesuai dengan style.css
if (!$artikel || !$artikel['id']) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="id"><head><title>404 Not Found</title><link rel="stylesheet" href="style.css"></head><body><div class="header" style="min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;"><h1>404</h1><p class="subtitle">Laporan yang Anda cari tidak ditemukan.</p><a href="artikel.php" class="read-more-btn" style="margin-top: 2rem;">Kembali ke Daftar Artikel</a></div></body></html>';
    exit;
}

// Query untuk sidebar Artikel Terkait
$first_category_id = 0;
if (!empty($artikel['category_ids'])) {
    $category_id_arr = explode(',', $artikel['category_ids']);
    $first_category_id = intval($category_id_arr[0]);
}
$query_related = "SELECT DISTINCT a.id, a.title, a.picture, a.date FROM article a 
                  JOIN article_category ac ON a.id = ac.article_id
                  WHERE ac.category_id = $first_category_id AND a.id != $id
                  ORDER BY a.date DESC LIMIT 4";
$result_related = mysqli_query($conn, $query_related);

// Query untuk sidebar kategori (digunakan juga di navbar)
$query_sidebar_cat = "SELECT name FROM category ORDER BY name ASC";
$result_sidebar_cat = mysqli_query($conn, $query_sidebar_cat);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Artikel</title>
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
                        <a class="nav-link dropdown-toggle" href="#" id="kategoriDropdown" role="button"
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
                    <li class="nav-item"><a class="nav-link" href="index.php#tentangsaya"> About Me</a></li>
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

    <main class="container py-5">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="post" data-aos="fade-up">
                    <?php if (!empty($artikel['picture'])): ?>
                        <img src="images/<?= htmlspecialchars($artikel['picture']) ?>"
                            alt="<?= htmlspecialchars($artikel['title']) ?>" class="detail-img">
                    <?php endif; ?>

                    <h2><?= htmlspecialchars($artikel['title']) ?></h2>

                    <p class="date">
                        <?= htmlspecialchars(date('d F Y', strtotime($artikel['date']))) ?>
                        <?php if (!empty($artikel['authors'])): ?>
                            &bull; Oleh <span class="author"><?= htmlspecialchars($artikel['authors']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($artikel['categories'])): ?>
                            &bull; Kategori: <span class="category"><?= htmlspecialchars($artikel['categories']) ?></span>
                        <?php endif; ?>
                    </p>

                    <?php
                    $author_ids_array = explode(',', $artikel['author_ids']);
                    if ($level == 'admin' || ($level == 'penulis' && in_array($user_id, $author_ids_array))):
                        ?>
                        <div class="d-flex align-items-center gap-2 my-3 p-2 rounded" style="background-color: #232946;">
                            <a href="CRUD/Edit.php?type=artikel&id=<?= $artikel['id'] ?>"
                                class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit Laporan
                                Ini</a>
                            <form method="POST" action="CRUD/Hapus.php" class="mb-0 delete-form-detail">
                                <input type="hidden" name="type" value="artikel">
                                <input type="hidden" name="id" value="<?= $artikel['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i>
                                    Hapus Laporan Ini</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <hr class="border-secondary">

                    <div class="post-content">
                        <?= $artikel['content'] ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div id="tentangsaya" class="post mb-4" data-aos="fade-left">
                        <h3 class="h5 p-3"><i class="bi bi-search me-2"></i>Cari Laporan Lain</h3>
                        <div class="p-3">
                            <form action="artikel.php" method="GET" class="d-flex">
                                <input type="text" name="cari" class="form-control me-2" placeholder="Cari..."
                                    style="background:rgb(255, 255, 255); color: #1e2746; border: none;">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                            </form>
                        </div>
                    </div>
                    <div id="tentangsaya" class="post mb-4" data-aos="fade-left" data-aos-delay="100">
                        <h3 class="h5 p-3"><i class="bi bi-lightning-charge-fill me-2"></i>Laporan Terkait</h3>
                        <div class="p-3">
                            <?php
                            if ($result_related && mysqli_num_rows($result_related) > 0):
                                while ($related = mysqli_fetch_assoc($result_related)):
                                    ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="detail.php?id=<?= $related['id'] ?>">
                                            <?php if (!empty($related['picture'])): ?>
                                                <img src="images/<?= htmlspecialchars($related['picture']) ?>" alt=""
                                                    class="img-fluid me-3"
                                                    style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px;">
                                            <?php else: ?>
                                                <img src="https://api.dicebear.com/8.x/initials/svg?seed=<?= urlencode(substr($related['title'], 0, 1)) ?>"
                                                    alt="" class="img-fluid me-3"
                                                    style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px; background-color: #eee;">
                                            <?php endif; ?>
                                        </a>
                                        <div>
                                            <h6 class="h6 mb-0"><a href="detail.php?id=<?= $related['id'] ?>"
                                                    class="nav-link p-0"><?= htmlspecialchars($related['title']) ?></a></h6>
                                            <small class="text-muted"><?= date('d M Y', strtotime($related['date'])) ?></small>
                                        </div>
                                    </div>
                                    <?php
                                endwhile;
                            else:
                                echo '<p class="text-muted small mt-1">Tidak ada artikel terkait lainnya.</p>';
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
        AOS.init({ duration: 800, once: true, offset: 50 });
        const deleteFormDetail = document.querySelector('.delete-form-detail');
        if (deleteFormDetail) {
            deleteFormDetail.addEventListener('submit', function (event) {
                event.preventDefault();
                if (confirm('Anda yakin ingin menghapus artikel ini secara permanen?')) {
                    const formData = new FormData(this);
                    fetch(this.action, { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('Artikel berhasil dihapus. Anda akan diarahkan ke halaman daftar artikel.');
                                window.location.href = 'artikel.php';
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Network error:', error));
                }
            });
        }
    </script>
</body>

</html>