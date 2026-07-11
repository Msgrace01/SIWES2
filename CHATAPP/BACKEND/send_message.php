<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'Chat.php';

$senderId    = isset($_POST['sender_id']) ? $_POST['sender_id'] : null;
$messageText = isset($_POST['message_text']) ? $_POST['message_text'] : null;

if (!$senderId || !$messageText) {
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true);

    if (!empty($jsonData)) {
        $senderId    = isset($jsonData['sender_id']) ? $jsonData['sender_id'] : $senderId;
        $messageText = isset($jsonData['message_text']) ? $jsonData['message_text'] : $messageText;
    }
}

if ($senderId && $messageText) {
    $chat = new Chat();
    $response = $chat->sendMessage((int)$senderId, $messageText);
    echo json_encode($response);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Missing required fields. Please provide sender_id and message_text."
    ]);
}
?>