<?php
session_start();
include 'koneksi.php';

// Jika sudah login, redirect ke index.php
if (isset($_SESSION['user_id']) && isset($_SESSION['level'])) {
    header('Location: index.php');
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $username_safe = mysqli_real_escape_string($conn, $username);

        // PERBAIKAN: Logika login disatukan untuk memeriksa hanya ke tabel 'register'
        $query = "SELECT * FROM register WHERE username = '$username_safe' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($user = mysqli_fetch_assoc($result)) {
            // PERBAIKAN: Verifikasi password tanpa hash, sesuai permintaan
            if ($password === $user['password']) {
                // Password cocok, set session
                $_SESSION['user_id'] = $user['id']; // Gunakan ID numerik yang unik
                $_SESSION['username'] = $user['username'];
                $_SESSION['namalengkap'] = $user['namalengkap'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['email'] = $user['email'];
                header('Location: index.php');
                exit;
            } else {
                // Password salah
                $message = 'Username atau password salah!';
            }
        } else {
            // Username tidak ditemukan
            $message = 'Username atau password salah!';
        }
    } else {
        $message = 'Username dan password wajib diisi!';
    }
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
    <div class="modal fade show" id="authModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.7);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title w-100 text-center" id="authModalLabel">Masuk / Daftar</h5>
                </div>
                <div class="modal-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <ul class="nav nav-tabs nav-justified mb-3" id="authTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab"
                                data-bs-target="#loginTab">Login</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="register.php" class="nav-link" id="register-tab">Register</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="authTabContent">
                        <div class="tab-pane fade show active" id="loginTab" role="tabpanel">
                            <form method="post" action="login.php" class="p-2">
                                <input type="text" name="username" class="form-control mb-3" placeholder="Username"
                                    required>
                                <div class="input-group mb-3">
                                    <input type="password" name="password" class="form-control" placeholder="Password"
                                        id="loginPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i
                                            class="bi bi-eye" id="eyeIcon"></i></button>
                                </div>
                                <div class="text-center mb-3">
                                    <a href="index.php">Masuk sebagai Pengunjung</a>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('loginPassword');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>

</html>