<?php
$page_title = "Kelola Kategori";
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
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                
                // Check if category name already exists
                $check_query = "SELECT id FROM categories WHERE name = :name";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":name", $name);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "Nama kategori sudah ada!";
                    $message_type = "danger";
                } else {
                    $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":name", $name);
                    $stmt->bindParam(":description", $description);
                    
                    if ($stmt->execute()) {
                        $message = "Kategori berhasil ditambahkan!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menambahkan kategori!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                
                // Check if category name already exists (except current)
                $check_query = "SELECT id FROM categories WHERE name = :name AND id != :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":name", $name);
                $check_stmt->bindParam(":id", $id);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "Nama kategori sudah ada!";
                    $message_type = "danger";
                } else {
                    $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":name", $name);
                    $stmt->bindParam(":description", $description);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Kategori berhasil diupdate!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal mengupdate kategori!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if category is being used by books
                $check_query = "SELECT COUNT(*) as count FROM books WHERE category_id = :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $id);
                $check_stmt->execute();
                $check = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($check['count'] > 0) {
                    $message = "Tidak dapat menghapus kategori yang masih digunakan oleh buku!";
                    $message_type = "danger";
                } else {
                    $query = "DELETE FROM categories WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Kategori berhasil dihapus!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menghapus kategori!";
                        $message_type = "danger";
                    }
                }
                break;
        }
    }
}

// Get categories with book count
$query = "SELECT c.*, COUNT(b.id) as book_count 
          FROM categories c 
          LEFT JOIN books b ON c.id = b.category_id 
          GROUP BY c.id 
          ORDER BY c.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-tags"></i> Kelola Kategori Buku</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Tambah Kategori
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Categories Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Buku</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php $no = 1; foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $category['name']; ?></strong>
                                </td>
                                <td><?php echo $category['description'] ?: '-'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['book_count']; ?> buku</span>
                                </td>
                                <td><?php echo formatDate($category['created_at']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($category['book_count'] == 0): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirmDelete('Apakah Anda yakin ingin menghapus kategori ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Tidak dapat dihapus karena masih digunakan">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada kategori</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Deskripsi kategori (opsional)"></textarea>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3" 
                                  placeholder="Deskripsi kategori (opsional)"></textarea>
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
function editCategory(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>