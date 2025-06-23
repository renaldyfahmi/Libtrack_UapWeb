<?php
$page_title = "Login";
require_once '../includes/header.php';

if (isLoggedIn()) {
    $redirect = getUserType() === 'admin' ? '../admin/dashboard.php' : '../member/dashboard.php';
    header("Location: $redirect");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $user_type = sanitize($_POST['user_type']);
    
    if ($user_type === 'admin') {
        $query = "SELECT id, username, password, full_name FROM admins WHERE username = :username";
    } else {
        $query = "SELECT id, username, password, full_name, status FROM members WHERE username = :username";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
       
        if ($user_type === 'member' && $user['status'] !== 'active') {
            $error = "Akun Anda tidak aktif. Silakan hubungi admin.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user_type;
            
            $redirect = $user_type === 'admin' ? '../admin/dashboard.php' : '../member/dashboard.php';
            header("Location: $redirect");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-book fa-3x text-primary mb-3" ></i>
                            <h3>Libtrack</h3>
                            <p class="text-muted">Silakan login untuk melanjutkan</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Login Sebagai</label>
                                <select class="form-select" name="user_type" required>
                                    <option value="">Pilih jenis pengguna</option>
                                    <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="member" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'member') ? 'selected' : ''; ?>>Anggota</option>
                                </select>
                                <div class="invalid-feedback">
                                    Pilih jenis pengguna.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Username harus diisi.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required>
                                    <div class="invalid-feedback">
                                        Password harus diisi.
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        
                        <div class="text-center mb-3">
                            <p class="mb-0">Belum punya akun? 
                                <a href="register.php" class="text-primary text-decoration-none" >
                                    <strong>Daftar di sini </strong> 
                                </a>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Demo Login:<br>
                                <strong>Admin:</strong> admin / password<br>
                                <strong>Member:</strong> member1 / password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>