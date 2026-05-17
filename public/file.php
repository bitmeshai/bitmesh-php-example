<?php

/**
 * CLI sample: multipart submit to POST /transcribe-recorded and poll GET /transcribe-recorded/{id}.
 *
 * Loads composer autoload + project .env (BITMESH_CONSUMER_KEY / BITMESH_CONSUMER_SECRET).
 *
 * Usage:
 *   php file.php
 *   php file.php https://api.bitmesh.ai /path/to/file.mp3
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

$envFile = $projectRoot . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

$consumerKey = getenv('BITMESH_CONSUMER_KEY') ?: '';
$consumerSecret = getenv('BITMESH_CONSUMER_SECRET') ?: '';

if ($consumerKey === '' || $consumerSecret === '') {
    fwrite(STDERR, "Set BITMESH_CONSUMER_KEY and BITMESH_CONSUMER_SECRET (e.g. in project .env).\n");
    exit(1);
}

// Optional CLI override: php file.php http://localhost:8003 /path/to/file.mp3
$baseApiUrl = $argv[1] ?? 'https://api.bitmesh.ai';

$audioPath = $argv[2] ?? (__DIR__ . '/test_audio.mp3');

$submitUrl = rtrim($baseApiUrl, '/').'/transcribe-recorded';

echo "==== Transcribe Recorded Endpoint Test ====\n";
echo "Base URL: {$baseApiUrl}\n";
echo "Submit URL: {$submitUrl}\n";
echo "Audio File: {$audioPath}\n\n";

if (! is_file($audioPath)) {
    echo "Audio file not found: {$audioPath}\n";
    exit(1);
}

$submitOAuthParams = generateOAuthParams('POST', $submitUrl, $consumerKey, $consumerSecret);
$submitAuthHeader = buildAuthorizationHeader($submitOAuthParams);

$nonFileFields = [
    'speech_models' => ['universal-2'],
];
ksort($nonFileFields);
$canonicalJson = json_encode($nonFileFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($canonicalJson === false) {
    echo "Failed to encode non-file fields.\n";
    exit(1);
}

$submitPayloadSignature = hash('sha256', $canonicalJson.$consumerKey.$submitOAuthParams['oauth_signature']);

echo "Submitting transcript job (multipart upload)...\n";
$submitResponse = sendMultipartRequest(
    $submitUrl,
    $audioPath,
    $nonFileFields,
    $submitAuthHeader,
    $submitPayloadSignature
);

echo "Submit HTTP Status: {$submitResponse['status_code']}\n";
echo "Submit Response:\n{$submitResponse['body']}\n\n";

if ($submitResponse['curl_error']) {
    echo "Curl Error on submit: {$submitResponse['curl_error']}\n";
    exit(1);
}

$submitJson = json_decode($submitResponse['body'], true);
if (! is_array($submitJson)) {
    echo "Submit response is not valid JSON.\n";
    exit(1);
}

$transcriptId = $submitJson['id'] ?? null;
if (! is_string($transcriptId) || $transcriptId === '') {
    echo "No transcript id returned. Cannot poll.\n";
    exit(1);
}

echo "Transcript ID: {$transcriptId}\n\n";

$pollUrl = rtrim($baseApiUrl, '/').'/transcribe-recorded/'.$transcriptId;
echo "Polling URL: {$pollUrl}\n";

$maxAttempts = 20;
$sleepSeconds = 3;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $pollOAuthParams = generateOAuthParams('GET', $pollUrl, $consumerKey, $consumerSecret);
    $pollAuthHeader = buildAuthorizationHeader($pollOAuthParams);
    $pollPayloadSignature = hash('sha256', $consumerKey.$pollOAuthParams['oauth_signature']);

    $pollResponse = sendJsonRequest(
        $pollUrl,
        'GET',
        '',
        $pollAuthHeader,
        $pollPayloadSignature
    );

    echo "\nPoll Attempt {$attempt}/{$maxAttempts}\n";
    echo "HTTP Status: {$pollResponse['status_code']}\n";
    echo "Body: {$pollResponse['body']}\n";

    if ($pollResponse['curl_error']) {
        echo "Curl Error on poll: {$pollResponse['curl_error']}\n";
        exit(1);
    }

    $pollJson = json_decode($pollResponse['body'], true);
    if (! is_array($pollJson)) {
        echo "Poll response is not valid JSON.\n";
        exit(1);
    }

    $status = $pollJson['status'] ?? null;
    if ($status === 'completed') {
        $duration = $pollJson['audio_duration'] ?? null;
        $text = $pollJson['text'] ?? '';

        echo "\nSUCCESS: Transcript completed.\n";
        if (is_numeric($duration)) {
            echo "Audio Duration (seconds): {$duration}\n";
        }
        echo "Transcript Preview:\n";
        echo substr((string) $text, 0, 500)."\n";
        exit(0);
    }

    if ($status === 'error') {
        $error = $pollJson['error'] ?? 'unknown';
        echo "\nFAILED: Transcript ended with error: ".(is_string($error) ? $error : json_encode($error))."\n";
        exit(1);
    }

    echo "Current status: ".(is_string($status) ? $status : 'unknown')."\n";

    if ($attempt < $maxAttempts) {
        sleep($sleepSeconds);
    }
}

echo "\nTimed out waiting for transcript completion.\n";
exit(1);

function sendMultipartRequest(string $url, string $audioPath, array $nonFileFields, string $authHeader, string $payloadSignature): array
{
    $headers = [
        'Authorization: '.$authHeader,
        'Accept: application/json',
        'User-Agent: BitmeshStandaloneTest/1.0',
        'X-Payload-Signature: '.$payloadSignature,
    ];

    $postFields = ['audio' => new CURLFile($audioPath)];
    foreach ($nonFileFields as $key => $value) {
        if (is_array($value)) {
            foreach (array_values($value) as $idx => $item) {
                $postFields[$key.'['.$idx.']'] = (string) $item;
            }
        } else {
            $postFields[$key] = (string) $value;
        }
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_TIMEOUT => 600,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $statusCode,
        'body' => is_string($responseBody) ? $responseBody : '',
        'curl_error' => $curlError,
    ];
}

function sendJsonRequest(string $url, string $method, string $body, string $authHeader, string $payloadSignature): array
{
    $headers = [
        'Authorization: '.$authHeader,
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: BitmeshStandaloneTest/1.0',
        'X-Payload-Signature: '.$payloadSignature,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 60,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $statusCode,
        'body' => is_string($responseBody) ? $responseBody : '',
        'curl_error' => $curlError,
    ];
}

function generateOAuthParams(string $method, string $url, string $consumerKey, string $consumerSecret): array
{
    $params = [
        'oauth_consumer_key' => $consumerKey,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => (string) time(),
        'oauth_nonce' => bin2hex(random_bytes(8)),
        'oauth_version' => '1.0',
    ];

    $params['oauth_signature'] = generateSignature($method, $url, $params, $consumerSecret);

    return $params;
}

function generateSignature(string $method, string $url, array $params, string $consumerSecret): string
{
    $parsedUrl = parse_url($url);
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = $parsedUrl['port'] ?? null;
    $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

    $normalizedUrl = $scheme.'://'.$host;
    if (($scheme === 'http' && $port !== null && $port !== 80) ||
        ($scheme === 'https' && $port !== null && $port !== 443)) {
        $normalizedUrl .= ':'.$port;
    }
    $normalizedUrl .= '/'.$path;

    $queryParams = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }

    $allParams = array_merge($params, $queryParams);
    unset($allParams['oauth_signature']);
    ksort($allParams);

    $normalizedParams = [];
    foreach ($allParams as $key => $value) {
        $normalizedParams[] = oauthUrlEncode($key).'='.oauthUrlEncode((string) $value);
    }
    $paramString = implode('&', $normalizedParams);

    $signatureBaseString = oauthUrlEncode($method).'&'.oauthUrlEncode($normalizedUrl).'&'.oauthUrlEncode($paramString);
    $signingKey = oauthUrlEncode($consumerSecret).'&';

    return base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
}

function buildAuthorizationHeader(array $oauthParams): string
{
    $headerParts = [];
    foreach ($oauthParams as $key => $value) {
        if (strpos($key, 'oauth_') === 0) {
            $headerParts[] = oauthUrlEncode($key).'="'.oauthUrlEncode((string) $value).'"';
        }
    }

    return 'OAuth '.implode(', ', $headerParts);
}

function oauthUrlEncode(string $data): string
{
    return str_replace('%7E', '~', rawurlencode($data));
}
