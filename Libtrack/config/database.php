<?php
class Database {
    private $host = "localhost";
    private $db_name = "perpustakaan_mini";
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function generateCode($prefix, $length = 3) {
    $database = new Database();
    $db = $database->getConnection();
    
    do {
        $number = str_pad(rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        $code = $prefix . $number;
        
        // Check if code exists based on prefix
        if ($prefix === 'L') {
            $query = "SELECT id FROM loans WHERE loan_code = :code";
        } elseif ($prefix === 'B') {
            $query = "SELECT id FROM books WHERE code = :code";
        } elseif ($prefix === 'M') {
            $query = "SELECT id FROM members WHERE member_code = :code";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();
    } while ($stmt->rowCount() > 0);
    
    return $code;
}

function confirmDelete($message) {
    return "return confirm('$message')";
}
?>