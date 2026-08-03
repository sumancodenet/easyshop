<?php
require_once __DIR__ . '/config.php';

/**
 * EasyPay Payment Gateway helpers
 */

function easypay_configured() {
  return EASYPAY_API_KEY !== '' && EASYPAY_SECRET_KEY !== '';
}

function easypay_headers() {
  return [
    'Content-Type: application/json',
    'x-api-key: ' . EASYPAY_API_KEY,
    'x-secret-key: ' . EASYPAY_SECRET_KEY,
  ];
}

/**
 * POST /create-order - creates a pending transaction
 */
function easypay_create_order($data) {
  $payload = [
    'amount'        => (float)$data['amount'],
    'orderId'       => $data['orderId'],
    'currency'      => $data['currency'] ?? 'INR',
    'customerName'  => $data['customerName'] ?? '',
    'customerEmail' => $data['customerEmail'] ?? '',
    'customerPhone' => $data['customerPhone'] ?? '',
    'redirectUrl'   => $data['redirectUrl'] ?? '',
    'webhookUrl'    => $data['webhookUrl'] ?? '',
  ];
  return easypay_request('/api/create-order', $payload);
}

/**
 * POST /verify-payment - submits UTR + proof (multipart)
 */
function easypay_verify_payment($data) {
  $url = rtrim(EASYPAY_BASE_URL, '/') . '/api/verify-payment';
  $post = [
    'transactionId'    => $data['transactionId'],
    'utrNumber'        => $data['utrNumber'],
    'paymentMethod'    => $data['paymentMethod'],
    'paymentAccountId' => $data['paymentAccountId'] ?? '',
  ];
  if (!empty($data['screenshot_path']) && file_exists($data['screenshot_path'])) {
    $post['screenshot'] = new CURLFile($data['screenshot_path']);
  }
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
      'x-api-key: ' . EASYPAY_API_KEY,
      'x-secret-key: ' . EASYPAY_SECRET_KEY,
    ],
    CURLOPT_TIMEOUT        => 30,
  ]);
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $json = json_decode($res, true);
  return ['http_code' => $code, 'body' => is_array($json) ? $json : ['success' => false, 'message' => 'Invalid gateway response']];
}

/**
 * Generic JSON POST helper
 */
function easypay_request($path, $payload) {
  $url = rtrim(EASYPAY_BASE_URL, '/') . $path;
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => easypay_headers(),
    CURLOPT_TIMEOUT        => 30,
  ]);
  $res = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $json = json_decode($res, true);
  return ['http_code' => $code, 'body' => is_array($json) ? $json : ['success' => false, 'message' => 'Invalid gateway response']];
}

/**
 * Verify HMAC-SHA256 signature of a callback payload
 * Builds string from signature_fields in the given order, then compares HMAC.
 */
function easypay_verify_signature($payload) {
  if (empty($payload['signature']) || empty($payload['signature_fields'])) {
    return false;
  }
  $signedKeys = array_map('trim', explode(',', $payload['signature_fields']));
  $parts = [];
  foreach ($signedKeys as $k) {
    $parts[] = $k . '=' . ($payload[$k] ?? '');
  }
  $str = implode('&', $parts);
  $expected = hash_hmac('sha256', $str, EASYPAY_SECRET_KEY);
  return hash_equals($expected, $payload['signature']);
}
