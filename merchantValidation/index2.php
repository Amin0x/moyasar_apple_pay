<?php

// Error Handling and Logging
function handleError($message) {
  header('Content-Type: application/json');
  echo json_encode(['error' => $message]);
  exit();
}

// Read Request Body
try {
  $json_post = json_decode(file_get_contents('php://input'));
  if (!$json_post) {
    throw new Exception('Invalid request body');
  }
} catch (Exception $e) {
  handleError($e->getMessage());
}

// Validate Input Data
$required_fields = ['validationURL'];
foreach ($required_fields as $field) {
  if (!isset($json_post->$field)) {
    handleError("Missing required field: $field");
  }
}

// Prepare CURL Request
$ch = curl_init();
$data = json_encode([
  'merchantIdentifier' => 'merchant.sa.ets',
  'initiative' => 'web',
  'initiativeContext' => 'ets.sa',
  'displayName' => 'Twasul Network',
]);
$headers = [
  'Content-type: application/json',
];

curl_setopt_array($ch, [
  CURLOPT_URL => $json_post->validationURL,
  CURLOPT_SSLCERT => './merchant_id.pem',
  CURLOPT_SSLKEY => './key.key',
  CURLOPT_POST => 1,
  CURLOPT_POSTFIELDS => $data,
  CURLOPT_HTTPHEADER => $headers,
  CURLOPT_RETURNTRANSFER => 1,
  // Optional security options (uncomment if needed)
  // CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
  // CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
  // CURLOPT_SSL_CIPHER_LIST => 'rsa_aes_128_gcm_sha_256,ecdhe_rsa_aes_128_gcm_sha_256',
]);

// Execute CURL Request
$response = curl_exec($ch);
if ($response === false) {
  handleError('CURL Error: ' . curl_error($ch));
}

curl_close($ch);

// Process Response
$decoded_response = json_decode($response);
if (!$decoded_response) {
  handleError('Invalid response format');
}

// Send Response
header('Content-Type: application/json');
echo json_encode($decoded_response);

