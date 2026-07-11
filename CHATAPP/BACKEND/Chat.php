<?php
// Chat.php
require_once 'DbInterface.php';

class Chat implements DbInterface {
    protected $conn;

    public function __construct() {
        $this->connect();
    }

    public function connect() {
        $host = "mysql-ruth.alwaysdata.net"; 
        $username = "ruth_chigozie"; 
        $password = "ru2th4.ch1"; 
        $dbname = "ruth_chatapp";

        try {
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

    // Post a message to the group
    public function sendMessage($senderId, $messageText) {
        $stmt = $this->conn->prepare("INSERT INTO group_messages (sender_id, message_text) VALUES (?, ?)");
        $stmt->bind_param("is", $senderId, $messageText);

        if ($stmt->execute()) {
            return ["status" => "success", "message" => "Message sent"];
        } else {
            return ["status" => "error", "message" => "Could not deliver message"];
        }
    }

    // Fetch the entire group feed history, linking usernames to the messages
    public function getGroupMessages() {
        $query = "
            SELECT gm.id, gm.message_text, gm.created_at, u.username, gm.sender_id 
            FROM group_messages gm 
            JOIN users u ON gm.sender_id = u.id 
            ORDER BY gm.created_at ASC
        ";
        $result = $this->conn->query($query);
        $messages = [];

        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        return ["status" => "success", "messages" => $messages];
    }
} // Class correctly ends here (removed the broken standalone execution line)
?>