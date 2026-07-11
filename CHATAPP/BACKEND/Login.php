<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'Authentication.php';

$username = isset($_POST['username']) ? $_POST['username'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;

if (!$username || !$password) {
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true);

    if (!empty($jsonData)) {
        $username = isset($jsonData['username']) ? $jsonData['username'] : $username;
        $password = isset($jsonData['password']) ? $jsonData['password'] : $password;
    }
}

if ($username && $password) {
    $auth = new Authentication();
    $response = $auth->loginUser($username, $password);
    echo json_encode($response);
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Missing credentials. Please provide both username and password."
    ]);
}
?>