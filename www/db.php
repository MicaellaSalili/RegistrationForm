<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, GET, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type'); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); 
    exit;
}

$jsonFile = __DIR__ . '/bookings.json';

// Ensure the JSON file exists
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required booking fields
    if (
        !isset($data['firstName']) ||
        !isset($data['lastName']) ||
        !isset($data['datetime']) ||
        !isset($data['email']) ||
        !isset($data['phone']) ||
        !isset($data['hours']) ||
        !isset($data['studentStatus']) ||
        !isset($data['totalPrice']) ||
        !isset($data['payment_method'])
    ) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required booking fields']);
        exit;
    }

    // Validate payment-specific fields
    if (in_array($data['payment_method'], ['gcash', 'maya', 'seabank']) && !isset($data['mobile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mobile number is required for selected payment method']);
        exit;
    }
    if ($data['payment_method'] === 'card' && (!isset($data['card_number']) || !isset($data['expiry_date']) || !isset($data['cvv']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Card details are required for card payment']);
        exit;
    }

    // Read existing bookings
    $bookings = json_decode(file_get_contents($jsonFile), true);

    // Add a new booking (with a simple auto-increment id)
    $newBooking = [
        'id' => count($bookings) + 1,
        'first_name' => $data['firstName'],
        'last_name' => $data['lastName'],
        'datetime' => $data['datetime'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'hours' => $data['hours'],
        'student_status' => $data['studentStatus'],
        'notes' => $data['notes'] ?? null,
        'total_price' => $data['totalPrice'],
        'payment_method' => $data['payment_method'],
        'payment_details' => [
            'mobile' => $data['mobile'] ?? null,
            'card_number' => $data['card_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'cvv' => $data['cvv'] ?? null,
            'card_type' => $data['card_type'] ?? null
        ],
        'created_at' => date('Y-m-d H:i:s')
    ];

    $bookings[] = $newBooking;

    // Save back to JSON file
    file_put_contents($jsonFile, json_encode($bookings, JSON_PRETTY_PRINT));

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Booking saved', 'booking' => $newBooking]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return all bookings
    $bookings = json_decode(file_get_contents($jsonFile), true);
    echo json_encode(['success' => true, 'bookings' => $bookings]);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
?>