CREATE DATABASE IF NOT EXISTS db_pendaftaran_mahasiswa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE db_pendaftaran_mahasiswa;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS learning_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id_mahasiswa VARCHAR(30) PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    alamat TEXT NOT NULL,
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_students_package
        FOREIGN KEY (package_id)
        REFERENCES learning_packages(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_students_user
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_students_nama (nama),
    INDEX idx_students_created_by (created_by)
) ENGINE=InnoDB;
INSERT INTO learning_packages (package_name, description)
SELECT 'Paket Reguler', 'Paket pembelajaran reguler.'
WHERE NOT EXISTS (
    SELECT 1 FROM learning_packages WHERE package_name = 'Paket Reguler'
);

INSERT INTO learning_packages (package_name, description)
SELECT 'Paket Karyawan', 'Paket pembelajaran untuk mahasiswa yang bekerja.'
WHERE NOT EXISTS (
    SELECT 1 FROM learning_packages WHERE package_name = 'Paket Karyawan'
);

INSERT INTO learning_packages (package_name, description)
SELECT 'Paket Online', 'Paket pembelajaran secara daring.'
WHERE NOT EXISTS (
    SELECT 1 FROM learning_packages WHERE package_name = 'Paket Online'
);
