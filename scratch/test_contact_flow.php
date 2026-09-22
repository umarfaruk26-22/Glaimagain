<?php
require_once __DIR__ . '/../config/config.php';

echo "Testing FastAPI Contact Dispatch from PHP...\n";

$testName = "Faruk Suratwala";
$testEmail = "umarfaruksuratwala@gmail.com";
$testMobile = "+91 98765 43210";
$testSubject = "Bespoke Sizing Consultation";
$testMessage = "Hello Concierge, I would like to request an exclusive sizing consultation for the Velvet Tuxedo Jacket in emerald.";

// 1. Insert into MySQL
$pdo = getDb();
$stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$testName, $testEmail, $testMobile, $testSubject, $testMessage]);
$msgId = $pdo->lastInsertId();
echo "[✓] MySQL Record Created with ID: {$msgId}\n";

// 2. Dispatch via FastAPI
$success = sendFastApiContactEmail($testName, $testEmail, $testMobile, $testSubject, $testMessage);
if ($success) {
    echo "[✓] FastAPI Microservice returned HTTP 200 SUCCESS!\n";
    echo "[✓] Email successfully queued and dispatched to umarfaruksuratwala@gmail.com\n";
} else {
    echo "[✗] FastAPI Microservice call failed.\n";
}
