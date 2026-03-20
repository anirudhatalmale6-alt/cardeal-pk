<?php
/**
 * Contact form API endpoint
 * Accepts POST requests from the frontend contact form
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// Get POST data (supports both form-data and JSON)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$car_interest = trim($data['car_interest'] ?? '');
$budget = trim($data['budget'] ?? '');
$message = trim($data['message'] ?? '');

// Validation
if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and phone number are required.']);
    exit;
}

// Basic phone validation (Pakistani format)
$cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
if (strlen($cleanPhone) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, car_interest, budget, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $car_interest, $budget, $message]);

    echo json_encode(['success' => true, 'message' => 'Thank you! We will contact you soon.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
