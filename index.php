<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    if ($email === '' || $passwordInput === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id, nama_lengkap, email, password_hash
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($passwordInput, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['nama_lengkap'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Email atau password yang Anda masukkan salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Pendaftaran Mahasiswa POLTEK</title>
</head>

<body>
    <header>
        <h1>Sistem Pendaftaran Mahasiswa</h1>
        <p>Silakan masuk untuk mengelola data mahasiswa.</p>
    </header>

    <main>
        <section>
            <h2>Login Pengguna</h2>

            <?php if ($flash): ?>
                <p role="status"><?= e($flash['message']) ?></p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <p>
                    <label for="email">Email</label><br>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" autocomplete="email"
                        required>
                </p>

                <p>
                    <label for="password">Password</label><br>
                    <input type="password" id="password" name="password" placeholder="Masukkan password"
                        autocomplete="current-password" required>
                </p>

                <button type="submit">Login</button>
            </form>

            <p>Belum memiliki akun? <a href="register.php">Daftar akun baru</a>.</p>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Sistem Pendaftaran Mahasiswa</p>
    </footer>
</body>

</html>