-- Buat database
CREATE DATABASE perpustakaanmini;
USE perpustakaanmini;

-- Tabel users untuk login
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    year INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

INSERT INTO books (title, author, year, description) VALUES 
('Laskar Pelangi', 'Andrea Hirata', 2005, 'Novel tentang perjuangan anak-anak Belitung'),
('Bumi Manusia', 'Pramoedya Ananta Toer', 1980, 'Novel sejarah Indonesia'),
('Ayat-Ayat Cinta', 'Habiburrahman El Shirazy', 2004, 'Novel religi dan cinta');