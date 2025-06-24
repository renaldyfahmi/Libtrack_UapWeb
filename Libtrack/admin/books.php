<?php
$page_title = "Data Buku";
require_once '../config/database.php';
require_once '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $code = sanitize($_POST['code']);
                $title = sanitize($_POST['title']);
                $author = sanitize($_POST['author']);
                $publisher = sanitize($_POST['publisher']);
                $publication_year = sanitize($_POST['publication_year']);
                $category_id = sanitize($_POST['category_id']);
                $isbn = sanitize($_POST['isbn']);
                $stock = (int)$_POST['stock'];
                $description = sanitize($_POST['description']);
                
                // Check if code already exists
                $check_query = "SELECT id FROM books WHERE code = :code";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":code", $code);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "Kode buku sudah ada!";
                    $message_type = "danger";
                } else {
                    $query = "INSERT INTO books (code, title, author, publisher, publication_year, category_id, isbn, stock, available_stock, description) 
                              VALUES (:code, :title, :author, :publisher, :publication_year, :category_id, :isbn, :stock, :stock, :description)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":code", $code);
                    $stmt->bindParam(":title", $title);
                    $stmt->bindParam(":author", $author);
                    $stmt->bindParam(":publisher", $publisher);
                    $stmt->bindParam(":publication_year", $publication_year);
                    $stmt->bindParam(":category_id", $category_id);
                    $stmt->bindParam(":isbn", $isbn);
                    $stmt->bindParam(":stock", $stock);
                    $stmt->bindParam(":description", $description);
                    
                    if ($stmt->execute()) {
                        $message = "Buku berhasil ditambahkan!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menambahkan buku!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = sanitize($_POST['title']);
                $author = sanitize($_POST['author']);
                $publisher = sanitize($_POST['publisher']);
                $publication_year = sanitize($_POST['publication_year']);
                $category_id = sanitize($_POST['category_id']);
                $isbn = sanitize($_POST['isbn']);
                $stock = (int)$_POST['stock'];
                $description = sanitize($_POST['description']);
                
                // Get current available stock
                $current_query = "SELECT stock, available_stock FROM books WHERE id = :id";
                $current_stmt = $db->prepare($current_query);
                $current_stmt->bindParam(":id", $id);
                $current_stmt->execute();
                $current = $current_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate new available stock
                $borrowed = $current['stock'] - $current['available_stock'];
                $new_available = $stock - $borrowed;
                
                if ($new_available < 0) {
                    $message = "Stok tidak boleh kurang dari jumlah yang sedang dipinjam (" . $borrowed . ")!";
                    $message_type = "danger";
                } else {
                    $query = "UPDATE books SET title = :title, author = :author, publisher = :publisher, 
                              publication_year = :publication_year, category_id = :category_id, isbn = :isbn, 
                              stock = :stock, available_stock = :available_stock, description = :description 
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":title", $title);
                    $stmt->bindParam(":author", $author);
                    $stmt->bindParam(":publisher", $publisher);
                    $stmt->bindParam(":publication_year", $publication_year);
                    $stmt->bindParam(":category_id", $category_id);
                    $stmt->bindParam(":isbn", $isbn);
                    $stmt->bindParam(":stock", $stock);
                    $stmt->bindParam(":available_stock", $new_available);
                    $stmt->bindParam(":description", $description);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Buku berhasil diupdate!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal mengupdate buku!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if book is being borrowed
                $check_query = "SELECT COUNT(*) as count FROM loans WHERE book_id = :id AND status IN ('pending', 'approved')";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $id);
                $check_stmt->execute();
                $check = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($check['count'] > 0) {
                    $message = "Tidak dapat menghapus buku yang sedang dipinjam!";
                    $message_type = "danger";
                } else {
                    $query = "DELETE FROM books WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Buku berhasil dihapus!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menghapus buku!";
                        $message_type = "danger";
                    }
                }
                break;
        }
    }
}

// Get categories for dropdown
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get books with category info
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (b.title LIKE :search OR b.author LIKE :search OR b.code LIKE :search)";
}

if ($category_filter > 0) {
    $query .= " AND b.category_id = :category_filter";
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(":search", $search_param);
}

if ($category_filter > 0) {
    $stmt->bindParam(":category_filter", $category_filter);
}

$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-book"></i> Data Buku</h1>
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

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan judul, penulis, atau kode..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Books Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) > 0): ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><strong><?php echo $book['code']; ?></strong></td>
                                <td><?php echo $book['title']; ?></td>
                                <td><?php echo $book['author']; ?></td>
                                <td><?php echo $book['category_name'] ?: '-'; ?></td>
                                <td><?php echo $book['publication_year']; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $book['available_stock']; ?></span> / 
                                    <span class="badge bg-secondary"><?php echo $book['stock']; ?></span>
                                </td>
                                <td>
                                    <?php if ($book['available_stock'] > 0): ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Dipinjam</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
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
                            <td colspan="8" class="text-center text-muted">Tidak ada data buku</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Buku Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label">Kode Buku *</label>
                                <input type="text" class="form-control" name="code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Buku *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="author" class="form-label">Penulis *</label>
                                <input type="text" class="form-control" name="author" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="publisher" class="form-label">Penerbit</label>
                                <input type="text" class="form-control" name="publisher">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="publication_year" class="form-label">Tahun Terbit</label>
                                <input type="number" class="form-control" name="publication_year" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stok *</label>
                                <input type="number" class="form-control" name="stock" min="1" value="1" required>
                            </div>
                        </div>
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

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editBookForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Buku</label>
                                <input type="text" class="form-control" id="edit_code" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Kategori</label>
                                <select class="form-select" name="category_id" id="edit_category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Judul Buku *</label>
                        <input type="text" class="form-control" name="title" id="edit_title" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_author" class="form-label">Penulis *</label>
                                <input type="text" class="form-control" name="author" id="edit_author" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_publisher" class="form-label">Penerbit</label>
                                <input type="text" class="form-control" name="publisher" id="edit_publisher">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_publication_year" class="form-label">Tahun Terbit</label>
                                <input type="number" class="form-control" name="publication_year" id="edit_publication_year" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn" id="edit_isbn">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_stock" class="form-label">Stok *</label>
                                <input type="number" class="form-control" name="stock" id="edit_stock" min="1" required>
                            </div>
                        </div>
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

<script>
function editBook(book) {
    document.getElementById('edit_id').value = book.id;
    document.getElementById('edit_code').value = book.code;
    document.getElementById('edit_title').value = book.title;
    document.getElementById('edit_author').value = book.author;
    document.getElementById('edit_publisher').value = book.publisher || '';
    document.getElementById('edit_publication_year').value = book.publication_year || '';
    document.getElementById('edit_category_id').value = book.category_id || '';
    document.getElementById('edit_isbn').value = book.isbn || '';
    document.getElementById('edit_stock').value = book.stock;
    document.getElementById('edit_description').value = book.description || '';
    
    new bootstrap.Modal(document.getElementById('editBookModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>