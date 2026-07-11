<?php
// Authentication.php
require_once 'DbInterface.php';

class Authentication implements DbInterface {
    protected $conn;

    public function __construct() {
        $this->connect();
        // Start session if not already active to track login states
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function connect() {
        $host = "mysql-ruth.alwaysdata.net"; 
        $username = "ruth_chigozie"; 
        $password = "ru2th4.ch1"; 
        $dbname = "ruth_chatapp";

        try {
            // Force mysqli to report errors silently so we can handle them manually
            mysqli_report(MYSQLI_REPORT_OFF);
            $this->conn = new mysqli($host, $username, $password, $dbname);

            if ($this->conn->connect_error) {
                http_response_code(500);
                die(json_encode(["status" => "error", "message" => "Database connection failed"]));
            }
        } catch (Exception $e) {
            http_response_code(500);
            die(json_encode(["status" => "error", "message" => "Critical DB Error: " . $e->getMessage()]));
        }
    } // connect() ends here cleanly now

    // Register User
    public function registerUser($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                return ["status" => "success", "message" => "Account created!", "user_id" => $stmt->insert_id];
            } else {
                return ["status" => "error", "message" => "Registration failed."];
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                return [
                    "status" => "error", 
                    "message" => "This username or email is already taken."
                ];
            }
            return [
                "status" => "error", 
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    // Login User
    public function loginUser($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                return [
                    "status" => "success", 
                    "message" => "Logged in successfully",
                    "user" => ["id" => $row['id'], "username" => $row['username']]
                ];
            }
        }
        return ["status" => "error", "message" => "Invalid credentials"];
    }

    // Logout User
    public function logoutUser() {
        session_unset();
        session_destroy();
        return ["status" => "success", "message" => "Logged out successfully"];
    }
} // Class now correctly closes at the very end of the file
?>