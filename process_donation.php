<?php
require 'vendor/autoload.php'; // Include Stripe PHP library
\Stripe\Stripe::setApiKey('sk_test_51PBFNmSC7quupKi5iIZu86dsbnmdpHZ5qU2oGQ2uw2bJqd9hVbnR88fPDLlbyT485veaSh1toVSYh76gbZY4uH3b00rkDMuCf4');

// Database connection
$host = 'localhost';
$dbname = 'ngo';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Handle POST request with JSON
$input = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($input['amount'], $input['email'])) {
    $email = $input['email'];
    $amount = $input['amount'] * 100; // Convert to cents

    try {
        // Create Payment Intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'inr',
            'payment_method_types' => ['card'],
            'receipt_email' => $email,
        ]);

        // Save to database (optional)
        $sql = "INSERT INTO donations (transaction_id, donor_email, amount, currency) 
                VALUES (:transaction_id, :donor_email, :amount, :currency)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transaction_id' => $paymentIntent->id,
            ':donor_email' => $email,
            ':amount' => $amount / 100,
            ':currency' => 'inr',
        ]);

        echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request.']);
}
?>

