<?php
// pages/ai_proxy.php
// Same-origin proxy for local AI service to avoid browser localhost/CORS issues on LAN devices.

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['detail' => 'Unauthorized']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['detail' => 'Method not allowed']);
    return;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);
if (!is_array($payload) || empty(trim((string)($payload['question'] ?? '')))) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['detail' => 'Question is required.']);
    return;
}

$aiUrl = getenv('AI_API_URL');
if (!$aiUrl) {
    $aiUrl = 'http://127.0.0.1:8008/ask';
}

$body = json_encode(['question' => trim((string)$payload['question'])], JSON_UNESCAPED_UNICODE);
if ($body === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['detail' => 'Failed to encode request body.']);
    return;
}

$status = 502;
$response = '';
$errorMessage = '';

if (function_exists('curl_init')) {
    $ch = curl_init($aiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    if ($response === false) {
        $errorMessage = curl_error($ch) ?: 'Failed to connect to local AI service.';
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (is_int($code) && $code > 0) {
        $status = $code;
    }
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 30,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($aiUrl, false, $context);
    if ($response === false) {
        $errorMessage = 'Failed to connect to local AI service.';
    }

    if (!empty($http_response_header) && is_array($http_response_header)) {
        $line = $http_response_header[0] ?? '';
        if (preg_match('/\s(\d{3})\s/', $line, $m)) {
            $status = (int)$m[1];
        }
    }
}

if ($response === false || $response === '') {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['detail' => $errorMessage ?: 'No response from local AI service.']);
    return;
}

http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
echo $response;
