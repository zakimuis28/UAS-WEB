<?php
session_start();
include '../koneksi.php';

// Atur header default untuk merespon sebagai JSON
header('Content-Type: application/json');

// Memastikan skrip hanya bisa diakses via metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode tidak diizinkan.',
        'html' => '<div class="alert alert-danger">Metode tidak diizinkan.</div>'
    ]);
    exit();
}

// Inisialisasi variabel sesi
$user_id = $_SESSION['user_id'] ?? null;
$level = $_SESSION['level'] ?? null;

// Memeriksa sesi harus login
if (!$user_id || !$level) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Silakan login terlebih dahulu.',
        'html' => '<div class="alert alert-danger">Akses ditolak. Silakan login terlebih dahulu.</div>'
    ]);
    exit();
}

// Mengambil data dari $_POST
$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);

if (!$type || !$id) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak valid.',
        'html' => '<div class="alert alert-danger">Parameter tidak valid.</div>'
    ]);
    exit();
}

$is_success = false;
$message = '';
$html = '';

// === Logika Hapus ===
if ($type === 'artikel') {
    // --- PERBAIKAN HAK AKSES PENULIS ---
    $can_delete = false;
    if ($level === 'admin') {
        $can_delete = true;
    } elseif ($level === 'penulis') {
        // Cari id author penulis dari email session
        $author_id = null;
        if (isset($_SESSION['email'])) {
            $email_safe = mysqli_real_escape_string($conn, $_SESSION['email']);
            $q_author = mysqli_query($conn, "SELECT id FROM author WHERE email='$email_safe' LIMIT 1");
            if ($row_author = mysqli_fetch_assoc($q_author)) {
                $author_id = intval($row_author['id']);
            }
        }
        // Cek apakah artikel ini milik penulis yang login
        if ($author_id) {
            $q_check_owner = mysqli_query($conn, "SELECT article_id FROM article_author WHERE article_id = $id AND author_id = $author_id");
            if (mysqli_num_rows($q_check_owner) > 0) {
                $can_delete = true;
            }
        }
    }

    if ($can_delete) {
        // Ambil nama file gambar sebelum dihapus dari DB
        $q_get_pic = mysqli_query($conn, "SELECT picture FROM article WHERE id = $id");
        $picture_to_delete = ($row = mysqli_fetch_assoc($q_get_pic)) ? $row['picture'] : null;

        // Hapus relasi dan artikel
        mysqli_query($conn, "DELETE FROM article_author WHERE article_id = $id");
        mysqli_query($conn, "DELETE FROM article_category WHERE article_id = $id");
        $q_delete_article = mysqli_query($conn, "DELETE FROM article WHERE id = $id");

        if ($q_delete_article) {
            // Jika berhasil, hapus juga file gambarnya
            if (!empty($picture_to_delete) && file_exists("../images/" . $picture_to_delete)) {
                unlink("../images/" . $picture_to_delete);
            }
            $is_success = true;
            $message = 'Artikel berhasil dihapus.';
            $html = '<div class="alert alert-success">Artikel berhasil dihapus.</div>';
        } else {
            $message = 'Gagal menghapus artikel.';
            $html = '<div class="alert alert-danger">Gagal menghapus artikel.</div>';
        }
    } else {
        http_response_code(403);
        $message = 'Akses ditolak. Anda tidak memiliki izin untuk menghapus artikel ini.';
        $html = '<div class="alert alert-danger">Akses ditolak. Anda tidak memiliki izin untuk menghapus artikel ini.</div>';
    }

} elseif ($type === 'kategori' || $type === 'penulis') {
    // Hanya admin yang bisa hapus kategori dan penulis
    if ($level !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak. Hanya admin yang bisa melakukan aksi ini.',
            'html' => '<div class="alert alert-danger">Akses ditolak. Hanya admin yang bisa melakukan aksi ini.</div>'
        ]);
        exit();
    }

    if ($type === 'kategori') {
        mysqli_query($conn, "DELETE FROM article_category WHERE category_id = $id");
        if (mysqli_query($conn, "DELETE FROM category WHERE id = $id")) {
            $is_success = true;
            $message = 'Kategori berhasil dihapus.';
            $html = '<div class="alert alert-success">Kategori berhasil dihapus.</div>';
        } else {
            $message = 'Gagal menghapus kategori.';
            $html = '<div class="alert alert-danger">Gagal menghapus kategori.</div>';
        }
    } elseif ($type === 'penulis') {
        // --- PERBAIKAN SINKRONISASI HAPUS PENULIS ---
        // Ambil dulu nickname dari tabel author untuk menghapus data di tabel register
        $q_get_author = mysqli_query($conn, "SELECT nickname FROM author WHERE id = $id");
        $author_data = mysqli_fetch_assoc($q_get_author);
        $nickname_to_delete = $author_data['nickname'] ?? null;

        // Hapus relasi dan data penulis
        mysqli_query($conn, "DELETE FROM article_author WHERE author_id = $id");
        if (mysqli_query($conn, "DELETE FROM author WHERE id = $id")) {
            // Jika berhasil, hapus juga dari tabel register
            if ($nickname_to_delete) {
                mysqli_query($conn, "DELETE FROM register WHERE username = '" . mysqli_real_escape_string($conn, $nickname_to_delete) . "'");
            }
            $is_success = true;
            $message = 'Penulis berhasil dihapus.';
            $html = '<div class="alert alert-success">Penulis berhasil dihapus.</div>';
        } else {
            $message = 'Gagal menghapus penulis.';
            $html = '<div class="alert alert-danger">Gagal menghapus penulis.</div>';
        }
    }
} else {
    http_response_code(400);
    $message = 'Tipe tidak dikenali.';
    $html = '<div class="alert alert-danger">Tipe tidak dikenali.</div>';
}

// === PERBAIKAN: Memberikan respon JSON dengan pesan HTML ===
if ($is_success) {
    echo json_encode(['status' => 'success', 'message' => $message, 'html' => $html]);
} else {
    // Jika tidak sukses dan belum ada http_response_code, set sebagai error server
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'message' => $message, 'html' => $html]);
}