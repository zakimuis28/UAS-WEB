<?php
session_start();
include '../koneksi.php';

// Cek hak akses dasar (harus login)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['level'])) {
    header('Location: ../login.php');
    exit();
}

$type = $_GET['type'] ?? 'artikel'; // Default ke 'artikel' jika tidak ada tipe
$level = $_SESSION['level'];
$user_id = $_SESSION['user_id']; // ID dari user yang login

// Hak akses spesifik per tipe
if (($type === 'penulis' || $type === 'kategori') && $level !== 'admin') {
    // Hanya admin yang bisa tambah penulis & kategori
    echo "Akses ditolak. Hanya admin yang bisa mengakses halaman ini.";
    exit();
}

$success = '';
$error = '';

// Logika untuk memproses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_type = $_POST['type'] ?? '';

    if ($current_type === 'artikel') {
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $tanggal = date("Y-m-d H:i:s");

        // ==================== PERBAIKAN: Ambil id author sebenarnya untuk penulis ====================
        $author_id = null;
        if ($level === 'admin') {
            $author_id = intval($_POST['author_id'] ?? 0);
        } else {
            // Cari id author berdasarkan email session
            if (isset($_SESSION['email'])) {
                $email_safe = mysqli_real_escape_string($conn, $_SESSION['email']);
                $q = mysqli_query($conn, "SELECT id FROM author WHERE email='$email_safe' LIMIT 1");
                if ($row = mysqli_fetch_assoc($q)) {
                    $author_id = intval($row['id']);
                }
            }
        }

        if (!empty($judul) && !empty($isi) && $author_id > 0 && $category_id > 0) {
            $gambar = '';
            if (isset($_FILES['gambar']) && !empty($_FILES['gambar']['name'])) {
                $gambar = date('YmdHis') . '_' . basename($_FILES["gambar"]["name"]);
                $target = "../images/" . $gambar;
                if (!is_dir("../images")) {
                    mkdir("../images", 0755, true);
                }
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target)) {
                    $error = 'Gagal mengupload gambar.';
                }
            }
            if (empty($error)) {
                $judul_safe = mysqli_real_escape_string($conn, $judul);
                $isi_safe = mysqli_real_escape_string($conn, $isi);
                $query = "INSERT INTO article (title, content, picture, date) VALUES ('$judul_safe', '$isi_safe', '$gambar', '$tanggal')";
                if (mysqli_query($conn, $query)) {
                    $article_id = mysqli_insert_id($conn);
                    mysqli_query($conn, "INSERT INTO article_author (article_id, author_id) VALUES ($article_id, $author_id)");
                    mysqli_query($conn, "INSERT INTO article_category (article_id, category_id) VALUES ($article_id, $category_id)");
                    $success = 'Artikel berhasil ditambahkan! Mengarahkan kembali...';
                    header("refresh:2;url=../artikel.php");
                } else {
                    $error = 'Gagal menambah artikel ke database.';
                }
            }
        } else {
            $error = 'Judul, Isi, Penulis, dan Kategori wajib diisi.';
        }

    } elseif ($current_type === 'penulis') {
        $nickname = mysqli_real_escape_string($conn, $_POST['nickname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        if (!empty($nickname) && !empty($email) && !empty($password)) {
            // Cek duplikasi username/email
            $q_cek = mysqli_query($conn, "SELECT * FROM register WHERE username='$nickname' OR email='$email'");
            $q_cek2 = mysqli_query($conn, "SELECT * FROM author WHERE email='$email'");
            if (mysqli_num_rows($q_cek) > 0 || mysqli_num_rows($q_cek2) > 0) {
                $error = "Username atau email sudah terdaftar.";
            } else {
                mysqli_query($conn, "INSERT INTO register (username, namalengkap, email, password, level) VALUES ('$nickname', '$nickname', '$email', '$password', 'penulis')");
                mysqli_query($conn, "INSERT INTO author (nickname, email, password) VALUES ('$nickname', '$email', '$password')");
                $success = "Penulis baru berhasil ditambahkan! Mengarahkan kembali...";
                header("refresh:2;url=../penulis.php");
            }
        } else {
            $error = "Semua field wajib diisi.";
        }

    } elseif ($current_type === 'kategori') {
        $name = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
        if (!empty($name)) {
            if (mysqli_query($conn, "INSERT INTO category (name) VALUES ('$name')")) {
                $success = 'Kategori berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambah kategori.';
            }
        } else {
            $error = 'Nama kategori tidak boleh kosong.';
        }
    }
}

// Ambil data untuk dropdown form artikel
$authors = mysqli_query($conn, "SELECT id, nickname FROM author ORDER BY nickname ASC");
$categories = mysqli_query($conn, "SELECT id, name FROM category ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah <?= ucfirst($type) ?> - Blog Lapor Pak!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@400;500&display=swap"
        rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Montserrat', Arial, sans-serif;
        }

        .form-control,
        .form-select {
            background-color: #1e2746 !important;
            color: #eaeaea !important;
            border: 1px solid #0f3460 !important;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #0f3460 !important;
            border-color: #f9d923 !important;
            box-shadow: none !important;
        }

        .ck-editor__editable_inline {
            min-height: 250px;
            color: #212529;
            background-color: #fff;
        }
    </style>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
        <!-- Navbar bisa diisi jika perlu -->
    </nav>
    <div class="header" data-aos="fade-down">
        <h1>Tambah <?= ucfirst($type) ?> Baru</h1>
        <p class="subtitle">Gunakan formulir di bawah untuk membuat data baru.</p>
    </div>

    <main class="main-content" data-aos="fade-up">
        <div class="post-edit">
            <form action="Tambah.php?type=<?= $type ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="<?= $type ?>">

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($type == 'artikel'): ?>
                    <h2 class="h3">Formulir Artikel</h2>
                    <hr class="border-secondary">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3"><label for="judul" class="form-label">Judul Artikel</label><input type="text"
                                    id="judul" name="judul" class="form-control" required></div>
                            <div class="mb-3"><label for="isi" class="form-label">Isi Artikel</label><textarea name="isi"
                                    id="isi" class="form-control"></textarea></div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Kategori --</option>
                                    <?php mysqli_data_seek($categories, 0);
                                    while ($c = mysqli_fetch_assoc($categories)): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php if ($level === 'admin'): ?>
                                <div class="mb-3">
                                    <label for="author_id" class="form-label">Penulis</label>
                                    <select name="author_id" id="author_id" class="form-select" required>
                                        <option value="" disabled selected>-- Pilih Penulis --</option>
                                        <?php mysqli_data_seek($authors, 0);
                                        while ($a = mysqli_fetch_assoc($authors)): ?>
                                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nickname']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3"><label for="gambar" class="form-label">Gambar Utama</label><input type="file"
                                    name="gambar" id="gambar" class="form-control"></div>
                        </div>
                    </div>

                <?php elseif ($type == 'penulis'): ?>
                    <h2 class="h3">Formulir Penulis</h2>
                    <hr class="border-secondary">
                    <div class="mb-3"><label for="nickname" class="form-label">Nama Penulis (Nickname)</label><input
                            type="text" id="nickname" name="nickname" class="form-control" required></div>
                    <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" id="email"
                            name="email" class="form-control" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password"
                            id="password" name="password" class="form-control" required></div>

                <?php elseif ($type == 'kategori'): ?>
                    <h2 class="h3">Formulir Kategori</h2>
                    <hr class="border-secondary">
                    <div class="mb-3"><label for="nama_kategori" class="form-label">Nama Kategori</label><input type="text"
                            id="nama_kategori" name="nama_kategori" class="form-control" required></div>
                <?php endif; ?>

                <div class="text-end mt-4">
                    <a href="../<?= $type ?>.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah
                        Sekarang</button>
                </div>
            </form>
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
        AOS.init({ duration: 800, once: true });
        <?php if ($type == 'artikel'): ?>
            ClassicEditor.create(document.querySelector('#isi')).catch(error => console.error(error));
        <?php endif; ?>
    </script>
</body>

</html>