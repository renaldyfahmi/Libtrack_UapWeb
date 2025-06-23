<?php
$page_title = "Profil Saya";
require_once '../config/database.php';
require_once '../includes/header.php';
requireMember();

$database = new Database();
$db = $database->getConnection();

$member_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    $check_query = "SELECT id FROM members WHERE email = :email AND id != :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $email);
    $check_stmt->bindParam(":id", $member_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $message = "Email sudah digunakan oleh anggota lain!";
        $message_type = "danger";
    } else {
        $query = "UPDATE members SET full_name = :full_name, email = :email, phone = :phone, address = :address WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":full_name", $full_name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":id", $member_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name; 
            $message = "Profil berhasil diupdate!";
            $message_type = "success";
        } else {
            $message = "Gagal mengupdate profil!";
            $message_type = "danger";
        }
    }
}

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi password tidak sama!";
        $message_type = "danger";
    } else {
        $verify_query = "SELECT password FROM members WHERE id = :id";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(":id", $member_id);
        $verify_stmt->execute();
        $user = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user['password'])) {
            $message = "Password lama tidak benar!";
            $message_type = "danger";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE members SET password = :password WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":password", $hashed_password);
            $update_stmt->bindParam(":id", $member_id);
            
            if ($update_stmt->execute()) {
                $message = "Password berhasil diubah!";
                $message_type = "success";
            } else {
                $message = "Gagal mengubah password!";
                $message_type = "danger";
            }
        }
    }
}

$query = "SELECT * FROM members WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $member_id);
$stmt->execute();
$member = $stmt->fetch(PDO::FETCH_ASSOC);

$stats_query = "SELECT 
                COUNT(*) as total_loans,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as active_loans,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_loans,
                COUNT(CASE WHEN status = 'approved' AND due_date < CURDATE() THEN 1 END) as overdue_loans
                FROM loans WHERE member_id = :member_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(":member_id", $member_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-user"></i> Profil Saya</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo $member['full_name']; ?></h5>
                <p class="text-muted"><?php echo $member['member_code']; ?></p>
                <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>">
                    <?php echo ucfirst($member['status']); ?>
                </span>
                <hr>
                <small class="text-muted">
                    Bergabung sejak: <?php echo formatDate($member['created_at']); ?>
                </small>
            </div>
        </div>
        

        <div class="card mt-4">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar"></i> Statistik Peminjaman</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?php echo $stats['total_loans']; ?></h4>
                        <small>Total</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success"><?php echo $stats['active_loans']; ?></h4>
                        <small>Aktif</small>
                    </div>
                    <div class="col-6 mt-3">
                        <h4 class="text-info"><?php echo $stats['returned_loans']; ?></h4>
                        <small>Dikembalikan</small>
                    </div>
                    <div class="col-6 mt-3">
                        <h4 class="text-danger"><?php echo $stats['overdue_loans']; ?></h4>
                        <small>Terlambat</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-edit"></i> Update Profil</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Anggota</label>
                                <input type="text" class="form-control" value="<?php echo $member['member_code']; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo $member['username']; ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo $member['full_name']; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $member['email']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo $member['phone']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo $member['address']; ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profil
                    </button>
                </form>
            </div>
        </div>
        
       
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-key"></i> Ubah Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Lama *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>