<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$error = '';
$success = '';
$old = [
    'id_mahasiswa' => '',
    'nama' => '',
    'alamat' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'package_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($old as $field => $default) {
        $old[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    if (
        $old['id_mahasiswa'] === ''
        || $old['nama'] === ''
        || $old['alamat'] === ''
        || $old['tempat_lahir'] === ''
        || $old['tanggal_lahir'] === ''
        || $old['package_id'] === ''
    ) {
        $error = 'Semua data mahasiswa wajib diisi.';
    } elseif (strlen($old['id_mahasiswa']) > 30) {
        $error = 'ID mahasiswa maksimal 30 karakter.';
    } elseif (strlen($old['nama']) > 120) {
        $error = 'Nama mahasiswa maksimal 120 karakter.';
    } elseif (strlen($old['tempat_lahir']) > 100) {
        $error = 'Tempat lahir maksimal 100 karakter.';
    } else {
        $date = DateTime::createFromFormat('!Y-m-d', $old['tanggal_lahir']);
        $dateErrors = DateTime::getLastErrors();
        $validDate = $date !== false
            && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            && $date->format('Y-m-d') === $old['tanggal_lahir'];

        if (!$validDate) {
            $error = 'Tanggal lahir tidak valid.';
        } else {
            $packageCheck = $pdo->prepare(
                'SELECT id FROM learning_packages WHERE id = :id AND is_active = 1 LIMIT 1'
            );
            $packageCheck->execute(['id' => $old['package_id']]);

            if (!$packageCheck->fetch()) {
                $error = 'Paket belajar yang dipilih tidak tersedia.';
            } else {
                try {
                    $insert = $pdo->prepare(
                        'INSERT INTO students
                            (id_mahasiswa, nama, alamat, tempat_lahir, tanggal_lahir, package_id, created_by)
                         VALUES
                            (:id_mahasiswa, :nama, :alamat, :tempat_lahir, :tanggal_lahir, :package_id, :created_by)'
                    );

                    $insert->execute([
                        'id_mahasiswa' => $old['id_mahasiswa'],
                        'nama' => $old['nama'],
                        'alamat' => $old['alamat'],
                        'tempat_lahir' => $old['tempat_lahir'],
                        'tanggal_lahir' => $old['tanggal_lahir'],
                        'package_id' => (int) $old['package_id'],
                        'created_by' => (int) $_SESSION['user_id'],
                    ]);

                    set_flash('success', 'Data mahasiswa berhasil disimpan ke database.');
                    header('Location: dashboard.php');
                    exit;
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $error = 'ID mahasiswa sudah digunakan. Silakan periksa kembali ID tersebut.';
                    } else {
                        $error = 'Data belum dapat disimpan. Silakan coba kembali.';
                    }
                }
            }
        }
    }
}

$packagesStatement = $pdo->query(
    'SELECT id, package_name, description
     FROM learning_packages
     WHERE is_active = 1
     ORDER BY package_name ASC'
);
$packages = $packagesStatement->fetchAll();

$studentsStatement = $pdo->prepare(
    'SELECT
        students.id_mahasiswa,
        students.nama,
        students.alamat,
        students.tempat_lahir,
        students.tanggal_lahir,
        learning_packages.package_name,
        students.created_at
     FROM students
     INNER JOIN learning_packages ON learning_packages.id = students.package_id
     WHERE students.created_by = :created_by
     ORDER BY students.created_at DESC'
);
$studentsStatement->execute(['created_by' => (int) $_SESSION['user_id']]);
$students = $studentsStatement->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Mahasiswa | Sistem Pendaftaran Mahasiswa POLTEK</title>
</head>
<body>
    <header>
        <h1>Sistem Pendaftaran Mahasiswa</h1>
        <p>Halaman pengelolaan data mahasiswa.</p>

        <nav aria-label="Navigasi utama">
            <p>
                Selamat datang, <strong><?= e($_SESSION['user_name']) ?></strong>.
            </p>
            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit">Logout</button>
            </form>
        </nav>
    </header>

    <main>
        <section>
            <h2>Form Pengisian Data Mahasiswa</h2>
            <p>Silakan lengkapi seluruh informasi mahasiswa di bawah ini.</p>

            <?php if ($flash): ?>
                <p role="status"><?= e($flash['message']) ?></p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <form action="dashboard.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <fieldset>
                    <legend>Identitas Mahasiswa</legend>

                    <p>
                        <label for="id_mahasiswa">ID Mahasiswa</label><br>
                        <input
                            type="text"
                            id="id_mahasiswa"
                            name="id_mahasiswa"
                            value="<?= e($old['id_mahasiswa']) ?>"
                            placeholder="Contoh: 20260001"
                            maxlength="30"
                            required
                        >
                    </p>

                    <p>
                        <label for="nama">Nama Mahasiswa</label><br>
                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            value="<?= e($old['nama']) ?>"
                            placeholder="Masukkan nama lengkap mahasiswa"
                            maxlength="120"
                            required
                        >
                    </p>

                    <p>
                        <label for="alamat">Alamat</label><br>
                        <textarea
                            id="alamat"
                            name="alamat"
                            rows="4"
                            cols="40"
                            placeholder="Masukkan alamat lengkap"
                            required
                        ><?= e($old['alamat']) ?></textarea>
                    </p>

                    <p>
                        <label for="tempat_lahir">Tempat Lahir</label><br>
                        <input
                            type="text"
                            id="tempat_lahir"
                            name="tempat_lahir"
                            value="<?= e($old['tempat_lahir']) ?>"
                            placeholder="Contoh: Jakarta"
                            maxlength="100"
                            required
                        >
                    </p>

                    <p>
                        <label for="tanggal_lahir">Tanggal Lahir</label><br>
                        <input
                            type="date"
                            id="tanggal_lahir"
                            name="tanggal_lahir"
                            value="<?= e($old['tanggal_lahir']) ?>"
                            required
                        >
                    </p>

                    <p>
                        <label for="package_id">Paket Belajar</label><br>
                        <select id="package_id" name="package_id" required>
                            <option value="">-- Pilih Paket Belajar --</option>
                            <?php foreach ($packages as $package): ?>
                                <option
                                    value="<?= e($package['id']) ?>"
                                    <?= (string) $old['package_id'] === (string) $package['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($package['package_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </fieldset>

                <p>
                    <button type="submit">Simpan Data Mahasiswa</button>
                    <button type="reset">Reset Form</button>
                </p>
            </form>
        </section>

        <hr>

        <section>
            <h2>Data Mahasiswa yang Telah Diinput</h2>
            <p>Daftar berikut menampilkan data yang Anda masukkan melalui akun ini.</p>

            <?php if (count($students) > 0): ?>
                <table border="1" cellpadding="8" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>ID Mahasiswa</th>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir</th>
                            <th>Paket Belajar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($student['id_mahasiswa']) ?></td>
                                <td><?= e($student['nama']) ?></td>
                                <td><?= e($student['alamat']) ?></td>
                                <td><?= e($student['tempat_lahir']) ?></td>
                                <td><?= e($student['tanggal_lahir']) ?></td>
                                <td><?= e($student['package_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Belum ada data mahasiswa yang disimpan.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Sistem Pendaftaran Mahasiswa</p>
    </footer>
</body>
</html>
