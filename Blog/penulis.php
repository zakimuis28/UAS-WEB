<?php
session_start();
include 'koneksi.php';

// Inisialisasi variabel sesi dengan aman
$user_id = $_SESSION['user_id'] ?? null;
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Pengunjung';
$level = isset($_SESSION['level']) ? $_SESSION['level'] : 'guest';

// Query untuk mengambil penulis beserta jumlah artikel mereka
$query_authors = "SELECT 
                    author.*, 
                    (SELECT COUNT(*) FROM article_author WHERE author_id = author.id) as post_count
                  FROM author 
                  ORDER BY nickname ASC";
$authors = mysqli_query($conn, $query_authors);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors</title>
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

        .author-card-wrapper {
            perspective: 1000px;
            /* Diperlukan untuk efek 3D saat hover */
        }

        .author-card {
            background: #232946;
            border-radius: 24px;
            border-left: 8px solid #f9d923;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .author-card:hover {
            transform: translateY(-10px) rotateX(5deg) scale(1.02);
            box-shadow: 0 15px 35px rgba(233, 69, 96, 0.3);
            border-left-color: #e94560;
        }

        .author-card-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #f9d923;
            box-shadow: 0 0 15px rgba(249, 217, 35, 0.4);
            margin-top: -50px;
            /* Membuat avatar setengah keluar dari card */
            z-index: 2;
            transition: transform 0.4s ease;
        }

        .author-card:hover .author-card-avatar {
            transform: scale(1.1);
        }

        .author-nickname {
            color: #f9d923;
            font-family: 'Montserrat', sans-serif;
        }

        .author-email {
            color: #aeb6cf;
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
                            $catres = mysqli_query($conn, "SELECT name FROM category ORDER BY name ASC");
                            while ($cat = mysqli_fetch_assoc($catres)) {
                                echo '<li><a class="dropdown-item" href="kategori.php?kategori=' . urlencode($cat['name']) . '">' . htmlspecialchars($cat['name']) . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link active" href="penulis.php"><i
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
        <h1>Kontributor</h1>
        <p class="subtitle">Kenali para kontributor di balik artikel-artikel menarik Blog Lapor Pak!.</p>
    </div>

    <main class="container py-5">
        <?php if ($level == 'admin'): ?>
            <div class="d-flex justify-content-end mb-4" data-aos="fade-up">
                <a href="CRUD/Tambah.php?type=penulis" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah
                    Penulis</a>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center g-4">
            <?php if ($authors && mysqli_num_rows($authors) > 0): ?>
                <?php
                $i = 0; // Inisialisasi counter untuk animasi
                while ($author = mysqli_fetch_assoc($authors)):
                    ?>
                    <div class="col-md-6 col-lg-4 author-card-wrapper" data-aos="fade-up"
                        data-aos-delay="<?= ($i % 3) * 100 ?>">
                        <div class="author-card text-center p-4 h-100 d-flex flex-column">
                            <img src="https://api.dicebear.com/8.x/initials/svg?seed=<?= urlencode($author['nickname']) ?>&backgroundColor=f9d923&backgroundType=gradient,solid"
                                alt="Avatar" class="author-card-avatar align-self-center">
                            <div class="mt-3 flex-grow-1">
                                <h2 class="h4 author-nickname mt-3"><?= htmlspecialchars($author['nickname']) ?></h2>
                                <p class="author-email small"><?= htmlspecialchars($author['email']) ?></p>
                                <span class="badge rounded-pill" style="background-color: #e94560;">
                                    <i class="bi bi-pencil-fill"></i> <?= $author['post_count'] ?> Artikel
                                </span>
                            </div>

                            <?php if ($level == 'admin'): ?>
                                <div class="mt-4 pt-4 border-top border-secondary d-flex justify-content-center gap-2">
                                    <a href="CRUD/Edit.php?type=penulis&id=<?= $author['id'] ?>"
                                        class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <form method="POST" action="CRUD/Hapus.php" class="mb-0 delete-form">
                                        <input type="hidden" name="type" value="penulis">
                                        <input type="hidden" name="id" value="<?= $author['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i>
                                            Hapus</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $i++;
                endwhile;
                ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="post text-center" data-aos="fade-up">
                        <h2>Belum Ada Penulis</h2>
                        <p>Saat ini belum ada penulis yang terdaftar di website ini.</p>
                    </div>
                </div>
            <?php endif; ?>
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

            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (confirm('Yakin ingin menghapus penulis ini? Artikel yang ditulis tidak akan terhapus.')) {
                        const formData = new FormData(form);
                        const cardWrapper = form.closest('.author-card-wrapper');
                        fetch(form.action, { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    cardWrapper.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                    cardWrapper.style.opacity = '0';
                                    cardWrapper.style.transform = 'scale(0.9)';
                                    setTimeout(() => cardWrapper.remove(), 500);
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Network error:', error);
                                alert('Terjadi masalah jaringan.');
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>