<?php
/**
 * GLAIMAGAIN - Razorpay Configuration & Signature Verification
 */

// Razorpay Test / Live API Keys
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_57m1uIeE7ZkU8J');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'eKq1Tq0h9N6Z7Y3vB9A5X1W8');
define('RAZORPAY_CURRENCY', 'INR');

/**
 * Verify Razorpay Payment Signature
 *
 * @param string $razorpayOrderId
 * @param string $razorpayPaymentId
 * @param string $razorpaySignature
 * @return bool
 */
function verifyRazorpaySignature(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): bool {
    $expectedSignature = hash_hmac(
        'sha256',
        $razorpayOrderId . '|' . $razorpayPaymentId,
        RAZORPAY_KEY_SECRET
    );

    return hash_equals($expectedSignature, $razorpaySignature);
}

/**
 * Create a Razorpay Order via cURL REST API
 *
 * @param int $amountInPaise Amount in lowest denomination (paise)
 * @param string $receipt Order receipt identifier
 * @param array $notes Custom notes
 * @return array
 */
function createRazorpayOrder(int $amountInPaise, string $receipt, array $notes = []): array {
    $url = 'https://api.razorpay.com/v1/orders';
    $data = [
        'amount' => $amountInPaise,
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => $receipt,
        'payment_capture' => 1,
        'notes' => $notes
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curlError,
            'order' => null
        ];
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($decoded['id'])) {
        return [
            'success' => true,
            'order' => $decoded
        ];
    }

    return [
        'success' => false,
        'error' => $decoded['error']['description'] ?? 'Unable to create Razorpay order.',
        'order' => $decoded
    ];
}
