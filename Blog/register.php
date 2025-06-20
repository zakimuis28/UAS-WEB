<?php
session_start(); // Mulai session di atas
include 'koneksi.php';
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $email = trim($_POST['email']);
    $namalengkap = trim($_POST['namalengkap']);
    $level = trim($_POST['level']);

    // Validasi input yang lebih rapi
    if (empty($username) || empty($password) || empty($email) || empty($namalengkap) || empty($level)) {
        $error_message = 'Semua data wajib diisi!';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Konfirmasi password tidak cocok!';
    } else {
        // Jika data lengkap dan password cocok, lanjutkan proses
        $username_safe = mysqli_real_escape_string($conn, $username);
        $email_safe = mysqli_real_escape_string($conn, $email);

        // Cek duplikasi username atau email
        $cek = mysqli_query($conn, "SELECT * FROM register WHERE username='$username_safe' OR email='$email_safe'");
        if (mysqli_num_rows($cek) > 0) {
            $error_message = 'Username atau email sudah terdaftar!';
        } else {
            // Jika tidak ada duplikasi, lanjutkan insert
            $namalengkap_safe = mysqli_real_escape_string($conn, $namalengkap);
            $level_safe = mysqli_real_escape_string($conn, $level);

            // Password tidak di-hash (SANGAT TIDAK AMAN, hanya contoh)
            $password_safe = mysqli_real_escape_string($conn, $password);

            $query_register = "INSERT INTO register (username, password, email, namalengkap, level)
                               VALUES ('$username_safe', '$password_safe', '$email_safe', '$namalengkap_safe', '$level_safe')";

            if (mysqli_query($conn, $query_register)) {
                // Jika levelnya 'penulis', insert juga ke tabel author
                if ($level_safe === 'penulis') {
                    $author_nickname = $username_safe;
                    $author_email = $email_safe;
                    $author_password = $password_safe;
                    // CEK DULU APAKAH EMAIL SUDAH ADA DI TABEL AUTHOR
                    $cek_author = mysqli_query($conn, "SELECT id FROM author WHERE email='$author_email'");
                    if (mysqli_num_rows($cek_author) == 0) {
                        mysqli_query($conn, "INSERT INTO author (nickname, email, password) VALUES ('$author_nickname', '$author_email', '$author_password')");
                    }
                }

                $success_message = 'Registrasi berhasil! Anda akan diarahkan ke halaman login dalam 3 detik.';
                header("refresh:3;url=login.php");
            } else {
                $error_message = 'Registrasi gagal. Terjadi kesalahan pada server.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Montserrat', Arial, sans-serif;
        }
    </style>
</head>

<body>
    <div class="modal fade show" id="authModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.7);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title w-100 text-center" id="authModalLabel">Masuk / Daftar</h5>
                </div>
                <div class="modal-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php elseif ($error_message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>

                    <ul class="nav nav-tabs nav-justified mb-3" id="authTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="login.php" class="nav-link">Login</a>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="register-tab" data-bs-toggle="tab"
                                data-bs-target="#registerContent">Register</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="authTabContent">
                        <div class="tab-pane fade show active" id="registerContent" role="tabpanel">
                            <form method="post" action="register.php" class="p-2">
                                <input type="text" name="username" class="form-control mb-3" placeholder="Username"
                                    required>
                                <input type="text" name="namalengkap" class="form-control mb-3"
                                    placeholder="Nama Lengkap" required>
                                <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                                <div class="input-group mb-3">
                                    <input type="password" name="password" class="form-control" placeholder="Password"
                                        id="password1" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <div class="input-group mb-3">
                                    <input type="password" name="password_confirm" class="form-control"
                                        placeholder="Konfirmasi Password" id="password2" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <select name="level" class="form-select mb-3" required>
                                    <option value="" disabled selected>Pilih Level</option>
                                    <option value="admin">Admin</option>
                                    <option value="penulis">Penulis</option>
                                </select>
                                <button type="submit" class="btn btn-success w-100">Daftar Akun</button>
                            </form>
                            <p class="text-center mt-3">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // UX: Javascript untuk toggle password
        function setupPasswordToggle(toggleId, passwordId) {
            const toggleButton = document.getElementById(toggleId);
            const passwordInput = document.getElementById(passwordId);
            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function () {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';

                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('bi-eye');
                        icon.classList.toggle('bi-eye-slash');
                    }
                });
            }
        }
        // Contoh penggunaan: sesuaikan ID-nya dengan elemen yang ada di HTML kamu
        setupPasswordToggle('togglePassword1', 'password1');
        setupPasswordToggle('togglePassword2', 'password2');
    </script>