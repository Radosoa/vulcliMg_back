#!/usr/bin/env php
<?php

$baseUrl = 'http://localhost:8000/api';

// Test data
$registerData = [
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

// Helper function to make requests
function makeRequest($url, $data = null, $token = null) {
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== TESTING AUTHENTICATION ENDPOINTS ===\n\n";

// Test 1: Register
echo "1. Testing POST /api/register\n";
echo "---\n";
$registerResponse = makeRequest("$baseUrl/register", $registerData);
echo "Status: " . $registerResponse['status'] . "\n";
echo "Response:\n" . json_encode($registerResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

$token = $registerResponse['body']['token'] ?? null;

if ($token) {
    // Test 2: Get User
    echo "2. Testing GET /api/user (with valid token)\n";
    echo "---\n";
    $userResponse = makeRequest("$baseUrl/user", null, $token);
    echo "Status: " . $userResponse['status'] . "\n";
    echo "Response:\n" . json_encode($userResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Logout
    echo "3. Testing POST /api/logout\n";
    echo "---\n";
    $logoutResponse = makeRequest("$baseUrl/logout", [], $token); // Send empty JSON body
    echo "Status: " . $logoutResponse['status'] . "\n";
    echo "Response:\n" . json_encode($logoutResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Verify token is revoked
    echo "4. Testing GET /api/user (with revoked token - should fail)\n";
    echo "---\n";
    $userResponse2 = makeRequest("$baseUrl/user", null, $token);
    echo "Status: " . $userResponse2['status'] . "\n";
    echo "Response:\n" . json_encode($userResponse2['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Login
    echo "5. Testing POST /api/login\n";
    echo "---\n";
    $loginData = [
        'email' => $registerData['email'],
        'password' => $registerData['password']
    ];
    $loginResponse = makeRequest("$baseUrl/login", $loginData);
    echo "Status: " . $loginResponse['status'] . "\n";
    echo "Response:\n" . json_encode($loginResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 6: Invalid credentials
    echo "6. Testing POST /api/login (invalid credentials)\n";
    echo "---\n";
    $invalidLoginData = [
        'email' => $registerData['email'],
        'password' => 'wrongpassword'
    ];
    $invalidLoginResponse = makeRequest("$baseUrl/login", $invalidLoginData);
    echo "Status: " . $invalidLoginResponse['status'] . "\n";
    echo "Response:\n" . json_encode($invalidLoginResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 7: Get user without token
    echo "7. Testing GET /api/user (without token - should fail)\n";
    echo "---\n";
    $userNoTokenResponse = makeRequest("$baseUrl/user");
    echo "Status: " . $userNoTokenResponse['status'] . "\n";
    echo "Response:\n" . json_encode($userNoTokenResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "Registration failed, skipping other tests.\n";
}

echo "=== TEST COMPLETE ===\n";
