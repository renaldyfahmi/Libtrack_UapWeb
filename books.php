<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';


if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = sanitize($_POST['title']);
                $author = sanitize($_POST['author']);
                $year = (int)$_POST['year'];
                $description = sanitize($_POST['description']);
                
                $query = "INSERT INTO books (title, author, year, description) VALUES (:title, :author, :year, :description)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":author", $author);
                $stmt->bindParam(":year", $year);
                $stmt->bindParam(":description", $description);
                
                if ($stmt->execute()) {
                    $message = "Buku berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menambahkan buku!";
                    $message_type = "danger";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = sanitize($_POST['title']);
                $author = sanitize($_POST['author']);
                $year = (int)$_POST['year'];
                $description = sanitize($_POST['description']);
                
                $query = "UPDATE books SET title = :title, author = :author, year = :year, description = :description WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":author", $author);
                $stmt->bindParam(":year", $year);
                $stmt->bindParam(":description", $description);
                $stmt->bindParam(":id", $id);
                
                if ($stmt->execute()) {
                    $message = "Buku berhasil diupdate!";
                    $message_type = "success";
                } else {
                    $message = "Gagal mengupdate buku!";
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                $query = "DELETE FROM books WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":id", $id);
                
                if ($stmt->execute()) {
                    $message = "Buku berhasil dihapus!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menghapus buku!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT * FROM books WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (title LIKE :search OR author LIKE :search)";
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(":search", $search_param);
}
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Libtrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-book"></i> Libtrack
                        </h4>
                        <small class="text-white-50">Perpustakaan Mini</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="books.php">
                                <i class="fas fa-book"></i> Kelola Buku
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-book"></i> Kelola Buku</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                            <i class="fas fa-plus"></i> Tambah Buku
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan judul atau penulis..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Penulis</th>
                                        <th>Tahun</th>
                                        <th>Deskripsi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($books) > 0): ?>
                                        <?php $no = 1; foreach ($books as $book): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo $book['title']; ?></strong></td>
                                                <td><?php echo $book['author']; ?></td>
                                                <td><?php echo $book['year']; ?></td>
                                                <td><?php echo substr($book['description'], 0, 50) . (strlen($book['description']) > 50 ? '...' : ''); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Tidak ada data buku</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

   
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Buku Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Buku *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">Penulis *</label>
                            <input type="text" class="form-control" name="author" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" name="year" min="1900" max="2024">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Judul Buku *</label>
                            <input type="text" class="form-control" name="title" id="edit_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_author" class="form-label">Penulis *</label>
                            <input type="text" class="form-control" name="author" id="edit_author" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_year" class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" name="year" id="edit_year" min="1900" max="2024">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBook(book) {
            document.getElementById('edit_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_year').value = book.year;
            document.getElementById('edit_description').value = book.description;
            
            new bootstrap.Modal(document.getElementById('editBookModal')).show();
        }

        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>