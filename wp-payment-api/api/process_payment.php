<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PipraPay\PaymentAPI\Service\PaymentGatewayService;

// Ensure strict JSON handling
header('Content-Type: application/json');

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    // Read and parse JSON input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }

    // Validate required fields
    $requiredFields = ['idempotency_key', 'wallet_id', 'amount', 'currency'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            exit;
        }
    }

    // Extract and cast input variables securely
    $idempotencyKey = (string) $data['idempotency_key'];
    $walletId = (string) $data['wallet_id'];
    $amount = (float) $data['amount'];
    $currency = (string) $data['currency'];

    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Amount must be greater than zero']);
        exit;
    }

    // Initialize the service and process payment
    $paymentService = new PaymentGatewayService();
    
    $responseDTO = $paymentService->processPayment(
        $idempotencyKey,
        $walletId,
        $amount,
        $currency
    );

    // Output secure JSON payload from DTO
    http_response_code(200);
    echo json_encode($responseDTO->toArray(), JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // In production, we'd log $e->getMessage() and return a generic error.
    // For demonstration purposes, returning the exception message.
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}
