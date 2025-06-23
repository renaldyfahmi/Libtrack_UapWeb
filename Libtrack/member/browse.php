<?php
$page_title = "Cari Buku";
require_once '../config/database.php';
require_once '../includes/header.php';
requireMember();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    $book_id = (int)$_POST['book_id'];
    $member_id = $_SESSION['user_id'];
   
    $check_query = "SELECT available_stock FROM books WHERE id = :book_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":book_id", $book_id);
    $check_stmt->execute();
    $book = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book || $book['available_stock'] <= 0) {
        $message = "Buku tidak tersedia untuk dipinjam!";
        $message_type = "danger";
    } else {
        $existing_query = "SELECT id FROM loans WHERE member_id = :member_id AND book_id = :book_id AND status IN ('pending', 'approved')";
        $existing_stmt = $db->prepare($existing_query);
        $existing_stmt->bindParam(":member_id", $member_id);
        $existing_stmt->bindParam(":book_id", $book_id);
        $existing_stmt->execute();
        
        if ($existing_stmt->rowCount() > 0) {
            $message = "Anda sudah meminjam atau mengajukan peminjaman buku ini!";
            $message_type = "warning";
        } else {
            $loan_code = generateCode('L', 4);
            $loan_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+14 days'));
            
            $insert_query = "INSERT INTO loans (loan_code, member_id, book_id, loan_date, due_date, status) 
                            VALUES (:loan_code, :member_id, :book_id, :loan_date, :due_date, 'pending')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(":loan_code", $loan_code);
            $insert_stmt->bindParam(":member_id", $member_id);
            $insert_stmt->bindParam(":book_id", $book_id);
            $insert_stmt->bindParam(":loan_date", $loan_date);
            $insert_stmt->bindParam(":due_date", $due_date);
            
            if ($insert_stmt->execute()) {
                $message = "Permintaan peminjaman berhasil diajukan! Menunggu persetujuan admin.";
                $message_type = "success";
            } else {
                $message = "Gagal mengajukan peminjaman!";
                $message_type = "danger";
            }
        }
    }
}

$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$availability = isset($_GET['availability']) ? sanitize($_GET['availability']) : '';

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

if ($availability === 'available') {
    $query .= " AND b.available_stock > 0";
} elseif ($availability === 'borrowed') {
    $query .= " AND b.available_stock = 0";
}

$query .= " ORDER BY b.title ASC";

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
    <h1 class="h2"><i class="fas fa-search"></i> Cari Buku</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-info"><?php echo count($books); ?> buku ditemukan</span>
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
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan judul, penulis, atau kode..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="availability">
                    <option value="">Semua Status</option>
                    <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                    <option value="borrowed" <?php echo $availability === 'borrowed' ? 'selected' : ''; ?>>Sedang Dipinjam</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <?php if (count($books) > 0): ?>
        <?php foreach ($books as $book): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0"><?php echo $book['title']; ?></h6>
                            <span class="badge bg-secondary"><?php echo $book['code']; ?></span>
                        </div>
                        
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-user"></i> <?php echo $book['author']; ?><br>
                                <?php if ($book['publisher']): ?>
                                    <i class="fas fa-building"></i> <?php echo $book['publisher']; ?><br>
                                <?php endif; ?>
                                <?php if ($book['category_name']): ?>
                                    <i class="fas fa-tag"></i> <?php echo $book['category_name']; ?><br>
                                <?php endif; ?>
                                <?php if ($book['publication_year']): ?>
                                    <i class="fas fa-calendar"></i> <?php echo $book['publication_year']; ?>
                                <?php endif; ?>
                            </small>
                        </p>
                        
                        <?php if ($book['description']): ?>
                            <p class="card-text">
                                <small><?php echo substr($book['description'], 0, 100) . (strlen($book['description']) > 100 ? '...' : ''); ?></small>
                            </p>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Stok:</span>
                                <div>
                                    <span class="badge bg-info"><?php echo $book['available_stock']; ?></span> / 
                                    <span class="badge bg-secondary"><?php echo $book['stock']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <?php if ($book['available_stock'] > 0): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> Tersedia
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> Sedang Dipinjam
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <?php if ($book['available_stock'] > 0): ?>
                            <button type="button" class="btn btn-primary btn-sm w-100" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);"
                                    onclick="borrowBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                <i class="fas fa-hand-paper"></i> Pinjam Buku
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="fas fa-ban"></i> Tidak Tersedia
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h4>Tidak ada buku ditemukan</h4>
                <p>Coba ubah kata kunci pencarian atau filter yang digunakan</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="borrowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin meminjam buku:</p>
                <h6 id="bookTitle" class="text-primary"></h6>
                <hr>
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Masa peminjaman adalah 14 hari. Permintaan akan diproses oleh admin terlebih dahulu.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="d-inline" id="borrowForm">
                    <input type="hidden" name="action" value="borrow">
                    <input type="hidden" name="book_id" id="borrowBookId">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                        <i class="fas fa-hand-paper"></i> Ya, Pinjam Buku
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function borrowBook(bookId, bookTitle) {
    document.getElementById('borrowBookId').value = bookId;
    document.getElementById('bookTitle').textContent = bookTitle;
    new bootstrap.Modal(document.getElementById('borrowModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>