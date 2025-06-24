-- Database Schema untuk Sistem Perpustakaan Mini
CREATE DATABASE perpustakaan_mini;
USE perpustakaan_mini;

-- Tabel Kategori Buku
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Admin/Petugas
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Anggota/Penyewa
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_code VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Buku
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    publisher VARCHAR(100),
    publication_year YEAR,
    category_id INT,
    isbn VARCHAR(20),
    stock INT DEFAULT 1,
    available_stock INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabel Peminjaman
CREATE TABLE loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_code VARCHAR(20) UNIQUE NOT NULL,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('pending', 'approved', 'returned', 'overdue') DEFAULT 'pending',
    notes TEXT,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- Insert data awal
INSERT INTO categories (name, description) VALUES
('Fiksi', 'Buku-buku fiksi dan novel'),
('Non-Fiksi', 'Buku-buku non-fiksi dan referensi'),
('Teknologi', 'Buku-buku tentang teknologi dan komputer'),
('Sejarah', 'Buku-buku sejarah dan biografi');

INSERT INTO admins (username, password, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@perpustakaan.com');
-- Password: password

INSERT INTO members (member_code, username, password, full_name, email, phone, address) VALUES
('M001', 'member1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john@email.com', '081234567890', 'Jl. Contoh No. 123'),
('M002', 'member2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane@email.com', '081234567891', 'Jl. Sample No. 456');
-- Password: password

INSERT INTO books (code, title, author, publisher, publication_year, category_id, isbn, stock, available_stock, description) VALUES
('B001', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 1, '9789793062792', 3, 3, 'Novel tentang perjuangan anak-anak Belitung'),
('B002', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 1, '9789799731234', 2, 2, 'Novel sejarah Indonesia'),
('B003', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 3, '9780132350884', 2, 2, 'Panduan menulis kode yang bersih'),
('B004', 'Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Gadjah Mada University Press', 2005, 4, '9789794202456', 1, 1, 'Sejarah Indonesia dari 1200-2004');