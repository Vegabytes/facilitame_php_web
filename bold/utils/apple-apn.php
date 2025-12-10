<?php
function sendApnsNotification($deviceToken, $subject, $body, $request_id)
{
    $authKeyPath = __DIR__ . "/AuthKey_GRAUDF432S.p8";
    $keyId = "GRAUDF432S";
    $teamId = "4V4KB8U9SR";
    $bundleId = "com.boldsoftware.facilitame";

    $url = "https://api.push.apple.com/3/device/$deviceToken";
    $authKey = file_get_contents($authKeyPath);

    // Generar un token JWT para APNs
    $header = [
        'alg' => 'ES256',
        'kid' => $keyId
    ];
    $claims = [
        'iss' => $teamId,
        'iat' => time()
    ];

    $headerEncoded = base64_encode(json_encode($header));
    $claimsEncoded = base64_encode(json_encode($claims));

    $privateKey = openssl_pkey_get_private($authKey);
    openssl_sign("$headerEncoded.$claimsEncoded", $signature, $privateKey, 'sha256');
    $jwt = "$headerEncoded.$claimsEncoded." . base64_encode($signature);

    // Construcción del payload de APNs
    if ($request_id !== 0)
    {
        $clean_request_id = preg_replace('/#.*$/', '', $request_id);
        $deeplink = '/(app)/tabs/mis-solicitudes/solicitud?id=' . $clean_request_id;

    }

    // Crear el payload en la misma estructura que FCM
    $payload = json_encode([
        'aps' => [
            'alert' => [
                'title' => $subject,
                'body'  => $body
            ],
            'sound' => 'default',
            // 'badge' => 1,
            // 'category' => 'customNotification',
            // 'mutable-content' => 1,
            // 'content-available' => 1
        ],
        // 'data' => [
        //     'deeplink' => $deeplink ?? '', // Se asegura de incluirlo solo si existe
        //     'request_id' => $clean_request_id ?? 0
        // ]
    ]);

    // Configurar los headers
    $headers = [
        "apns-topic: $bundleId",
        "authorization: bearer $jwt",
        "apns-push-type: alert"
    ];

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PORT, 443);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);

    // Ejecutar la petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response' => $response
    ];
}

// $result = sendApnsNotification($deviceToken, $bundleId, $authKeyPath, $keyId, $teamId);

// echo "HTTP Code: " . $result['http_code'] . "\n";
// echo "Response: " . $result['response'] . "\n";
