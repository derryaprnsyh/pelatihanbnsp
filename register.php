<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $passwordInput === '' || $konfirmasiPassword === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($passwordInput) < 8) {
        $error = 'Password minimal terdiri dari 8 karakter.';
    } elseif ($passwordInput !== $konfirmasiPassword) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkEmail->execute(['email' => $email]);

        if ($checkEmail->fetch()) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $passwordHash = password_hash($passwordInput, PASSWORD_DEFAULT);

            $insert = $pdo->prepare(
                'INSERT INTO users (nama_lengkap, email, password_hash)
                 VALUES (:nama_lengkap, :email, :password_hash)'
            );

            $insert->execute([
                'nama_lengkap' => $nama,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);

            set_flash('success', 'Pendaftaran berhasil. Silakan login menggunakan akun Anda.');
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | Sistem Pendaftaran Mahasiswa POLTEK</title>
</head>
<body>
    <header>
        <h1>Sistem Pendaftaran Mahasiswa</h1>
        <p>Buat akun baru untuk mulai mengisi data mahasiswa.</p>
    </header>

    <main>
        <section>
            <h2>Pendaftaran Akun Baru</h2>

            <?php if ($error !== ''): ?>
                <p role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <p>
                    <label for="nama_lengkap">Nama Lengkap</label><br>
                    <input
                        type="text"
                        id="nama_lengkap"
                        name="nama_lengkap"
                        placeholder="Masukkan nama lengkap"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >
                </p>

                <p>
                    <label for="email">Email</label><br>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nama@email.com"
                        maxlength="150"
                        autocomplete="email"
                        required
                    >
                </p>

                <p>
                    <label for="password">Password</label><br>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </p>

                <p>
                    <label for="konfirmasi_password">Konfirmasi Password</label><br>
                    <input
                        type="password"
                        id="konfirmasi_password"
                        name="konfirmasi_password"
                        placeholder="Ulangi password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </p>

                <button type="submit">Daftar Sekarang</button>
            </form>

            <p>Sudah memiliki akun? <a href="index.php">Kembali ke halaman login</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Sistem Pendaftaran Mahasiswa</p>
    </footer>
</body>
</html>
