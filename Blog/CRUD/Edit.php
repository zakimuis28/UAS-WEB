<?php
session_start();
include '../koneksi.php';

// Inisialisasi variabel sesi
$user_id = $_SESSION['user_id'] ?? null;
$level = $_SESSION['level'] ?? null;

// Pengecekan hak akses dasar: harus login
if (!$user_id || !$level) {
    header('Location: ../login.php');
    exit();
}

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$success = '';
$error = '';

if (!$type || !$id) {
    header('Location: ../index.php');
    exit;
}

// === LOGIKA MENGAMBIL DATA AWAL UNTUK FORM ===
$data = null;
if ($type === 'artikel') {
    // Ambil semua author id yang terkait dengan artikel ini
    $q = mysqli_query($conn, "SELECT a.*, 
        GROUP_CONCAT(aa.author_id) as author_ids 
        FROM article a 
        LEFT JOIN article_author aa ON a.id=aa.article_id 
        WHERE a.id=$id 
        GROUP BY a.id 
        LIMIT 1");
    $data = mysqli_fetch_assoc($q);
    if ($data) {
        $catQ = mysqli_query($conn, "SELECT category_id FROM article_category WHERE article_id=$id");
        $data['category_id'] = $catQ ? (mysqli_fetch_assoc($catQ)['category_id'] ?? '') : '';
        $data['author_ids_array'] = array_map('intval', explode(',', $data['author_ids']));
    }
} elseif ($type === 'kategori' || $type === 'penulis') {
    // Hanya admin yang boleh edit kategori dan penulis
    if ($level !== 'admin') {
        echo "Akses ditolak. Anda tidak memiliki izin untuk halaman ini.";
        exit;
    }
    $tableName = ($type === 'penulis') ? 'author' : 'category';
    $q = mysqli_query($conn, "SELECT * FROM `$tableName` WHERE id=$id LIMIT 1");
    $data = mysqli_fetch_assoc($q);
}

if (!$data) {
    header('Location: ../index.php?status=notfound');
    exit;
}

// =================== PERBAIKAN HAK AKSES PENULIS ===================
// Penulis hanya bisa mengedit artikelnya sendiri
if ($level === 'penulis' && $type === 'artikel') {
    // Cari id author penulis dari email session
    $author_id = null;
    if (isset($_SESSION['email'])) {
        $email_safe = mysqli_real_escape_string($conn, $_SESSION['email']);
        $q_author = mysqli_query($conn, "SELECT id FROM author WHERE email='$email_safe' LIMIT 1");
        if ($row_author = mysqli_fetch_assoc($q_author)) {
            $author_id = intval($row_author['id']);
        }
    }
    // Cek apakah id author penulis ada di array author_ids artikel
    if (!$author_id || !in_array($author_id, $data['author_ids_array'])) {
        echo "Akses ditolak. Anda hanya bisa mengedit artikel Anda sendiri.";
        exit;
    }
}

// === LOGIKA UPDATE DATA (POST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_type = $_POST['type'] ?? '';

    if ($current_type === 'artikel') {
        $title = mysqli_real_escape_string($conn, $_POST['judul']);
        $content = mysqli_real_escape_string($conn, $_POST['isi']);
        $category_id = intval($_POST['category_id']);
        // Untuk admin bisa pilih author, untuk penulis pakai author_id sendiri
        if ($level === 'admin') {
            $author_id_update = intval($_POST['author_id']);
        } else {
            // Cari id author penulis dari email session
            $author_id_update = null;
            if (isset($_SESSION['email'])) {
                $email_safe = mysqli_real_escape_string($conn, $_SESSION['email']);
                $q_author = mysqli_query($conn, "SELECT id FROM author WHERE email='$email_safe' LIMIT 1");
                if ($row_author = mysqli_fetch_assoc($q_author)) {
                    $author_id_update = intval($row_author['id']);
                }
            }
        }
        $picture = $data['picture'];

        if (isset($_FILES['gambar']) && !empty($_FILES['gambar']['name'])) {
            $picture_new = date('YmdHis') . '_' . basename($_FILES['gambar']['name']);
            $target = '../images/' . $picture_new;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target)) {
                if (!empty($picture) && file_exists('../images/' . $picture)) {
                    unlink('../images/' . $picture);
                }
                $picture = $picture_new;
            } else {
                $error = "Gagal mengupload gambar baru.";
            }
        }
        if (empty($error)) {
            mysqli_query($conn, "UPDATE article SET title='$title', content='$content', picture='$picture' WHERE id=$id");
            mysqli_query($conn, "UPDATE article_category SET category_id=$category_id WHERE article_id=$id");
            // Update author hanya jika admin atau penulis (tapi author_id_update sudah aman)
            if ($author_id_update) {
                mysqli_query($conn, "UPDATE article_author SET author_id=$author_id_update WHERE article_id=$id");
            }
            $success = 'Artikel berhasil diperbarui! Mengarahkan kembali...';
            header("refresh:2;url=../artikel.php");
            exit();
        }
    } elseif ($current_type === 'kategori') {
        $name = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
        if (!empty($name)) {
            if (mysqli_query($conn, "UPDATE category SET name='$name' WHERE id=$id")) {
                $success = 'Kategori berhasil diperbarui! Mengarahkan kembali...';
                header("refresh:2;url=../kategori.php");
                exit();
            } else {
                $error = 'Gagal memperbarui kategori.';
            }
        } else {
            $error = 'Nama kategori tidak boleh kosong.';
        }
    } elseif ($current_type === 'penulis') {
        $nickname = mysqli_real_escape_string($conn, $_POST['nickname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password_sql = "";
        if (!empty($_POST['password'])) {
            $password_plain = mysqli_real_escape_string($conn, $_POST['password']);
            $password_sql = ", password='$password_plain'";
            mysqli_query($conn, "UPDATE register SET password='$password_plain' WHERE username='" . $data['nickname'] . "'");
        }
        $query_update_author = "UPDATE author SET nickname='$nickname', email='$email' $password_sql WHERE id=$id";
        if (mysqli_query($conn, $query_update_author)) {
            mysqli_query($conn, "UPDATE register SET username='$nickname', email='$email' WHERE username='" . $data['nickname'] . "'");
            $success = 'Data penulis berhasil diperbarui! Mengarahkan kembali...';
            header("refresh:2;url=../penulis.php");
            exit();
        } else {
            $error = "Gagal memperbarui data penulis.";
        }
    }
}

// Ambil semua penulis dan kategori untuk dropdown
$authors = mysqli_query($conn, "SELECT id, nickname FROM author ORDER BY nickname ASC");
$categories = mysqli_query($conn, "SELECT id, name FROM category ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= ucfirst($type) ?> - Blog Lapor Pak!</title>
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
    <div class="header" data-aos="fade-down">
        <h1>Edit <?= ucfirst($type) ?></h1>
        <p class="subtitle">Ubah detail data di bawah ini.</p>
    </div>
    <main class="main-content" data-aos="fade-up">
        <div class="post-edit">
            <form action="Edit.php?type=<?= $type ?>&id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="<?= $type ?>">

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <?php if ($type == 'artikel'): ?>
                    <h2 class="h3">Formulir Edit Artikel</h2>
                    <hr class="border-secondary">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3"><label for="judul" class="form-label">Judul Artikel</label><input type="text"
                                    id="judul" name="judul" class="form-control" required
                                    value="<?= htmlspecialchars($data['title']) ?>"></div>
                            <div class="mb-3"><label for="isi" class="form-label">Isi Artikel</label><textarea name="isi"
                                    id="isi" class="form-control"><?= htmlspecialchars($data['content']) ?></textarea></div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <?php mysqli_data_seek($categories, 0);
                                    while ($c = mysqli_fetch_assoc($categories)):
                                        $selected = ($c['id'] == $data['category_id']) ? 'selected' : ''; ?>
                                        <option value="<?= $c['id'] ?>" <?= $selected ?>><?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="author_id" class="form-label">Penulis</label>
                                <select name="author_id" id="author_id" class="form-select" <?= ($level === 'penulis') ? 'disabled' : 'required' ?>>
                                    <?php mysqli_data_seek($authors, 0);
                                    while ($a = mysqli_fetch_assoc($authors)):
                                        $selected = ($a['id'] == ($data['author_ids_array'][0] ?? 0)) ? 'selected' : ''; ?>
                                        <option value="<?= $a['id'] ?>" <?= $selected ?>><?= htmlspecialchars($a['nickname']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($level === 'penulis'): ?><small class="text-muted">Anda tidak dapat mengubah
                                        penulis.</small><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gambar Saat Ini:</label><br>
                                <?php if (!empty($data['picture'])): ?><img
                                        src="../images/<?= htmlspecialchars($data['picture']) ?>" class="img-fluid rounded mb-2"
                                        style="max-width:200px; border-radius: 8px !important;"><?php endif; ?>
                                <input type="file" name="gambar" class="form-control">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar.</small>
                            </div>
                        </div>
                    </div>

                <?php elseif ($type == 'kategori'): ?>
                    <h2 class="h3">Formulir Edit Kategori</h2>
                    <hr class="border-secondary">
                    <div class="mb-3"><label for="nama_kategori" class="form-label">Nama Kategori</label><input type="text"
                            id="nama_kategori" name="nama_kategori" class="form-control" required
                            value="<?= htmlspecialchars($data['name']) ?>"></div>

                <?php elseif ($type == 'penulis'): ?>
                    <h2 class="h3">Formulir Edit Penulis</h2>
                    <hr class="border-secondary">
                    <div class="mb-3"><label for="nickname" class="form-label">Nama Penulis (Nickname)</label><input
                            type="text" id="nickname" name="nickname" class="form-control" required
                            value="<?= htmlspecialchars($data['nickname']) ?>"></div>
                    <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" id="email"
                            name="email" class="form-control" required value="<?= htmlspecialchars($data['email']) ?>">
                    </div>
                    <div class="mb-3"><label for="password" class="form-label">Password Baru</label><input type="password"
                            id="password" name="password" class="form-control"><small class="text-muted">Kosongkan kolom ini
                            jika tidak ingin mengubah password.</small></div>
                <?php endif; ?>

                <div class="text-end mt-4">
                    <a href="../<?= $type === 'kategori' ? 'kategori' : $type ?>.php"
                        class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Simpan Perubahan</button>
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