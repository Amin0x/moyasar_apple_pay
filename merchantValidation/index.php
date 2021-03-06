<?php
//create apple pay session

$json_post = json_decode(file_get_contents('php://input'));

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

curl_setopt($ch, CURLOPT_URL, $json_post->validationURL);
curl_setopt($ch, CURLOPT_SSLCERT, './merchant_id.pem');
curl_setopt($ch, CURLOPT_SSLKEY, './key.key');
//curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
//curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
//curl_setopt($ch, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_TLSv1_2');
//curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'rsa_aes_128_gcm_sha_256,ecdhe_rsa_aes_128_gcm_sha_256');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = curl_exec($ch);
if($response === false)
{
    header('Content-Type: application/json');
    echo json_encode(['curlError' => curl_error($ch)]); 
}

// close cURL resource, and free up system resources
curl_close($ch);

//header('HTTP/1.1 201 Creatied');
header('Content-Type: application/json');
echo json_encode(json_decode($response));
