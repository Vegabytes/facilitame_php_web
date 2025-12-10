<?php
use Google\Client as GoogleClient;

function getAccessToken($serviceAccountPath)
{
    $client = new GoogleClient();
    $client->setAuthConfig($serviceAccountPath);

    $client->addScope('https://www.googleapis.com/auth/cloud-platform');

    $accessTokenInfo = $client->fetchAccessTokenWithAssertion();
    if (isset($accessTokenInfo['access_token'])) {
        return $accessTokenInfo['access_token'];
    }

    return null;
}

function sendFcmNotification($fcmToken, $title, $body, $request_id = 0)
{
    $file_name_dir = ROOT_DIR . "/push-notifications.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . "sendFcmNotification :: inicio" . "\n", FILE_APPEND | LOCK_EX);

    $serviceAccountPath = __DIR__ . '/firebase_service_account.json';

    $accessToken = getAccessToken($serviceAccountPath);
    if (!$accessToken) {
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . "sendFcmNotification :: no hay access token" . "\n", FILE_APPEND | LOCK_EX);
        die('Error: No se pudo obtener el token de acceso');
    }    

    // Endpoint de la API v1
    $url = 'https://fcm.googleapis.com/v1/projects/facilitame-6ab1d/messages:send';

    $data = [
        'message' => [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body'  => $body
            ]
        ]
    ];

    if ($request_id !== 0)
    {        
        $clean_request_id = preg_replace('/#.*$/', '', $request_id);
        $deeplink = '/(app)/tabs/mis-solicitudes/solicitud?id=' . $clean_request_id;
        $data["message"]["data"] = [];
        $data["message"]["data"]["deeplink"] = $deeplink;
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . $deeplink . "\n", FILE_APPEND | LOCK_EX);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_TIMEOUT => 5,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            "Authorization: Bearer $accessToken"
        ],
        CURLOPT_POSTFIELDS     => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . json_encode($response) . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . "sendFcmNotification :: fin" . "\n", FILE_APPEND | LOCK_EX);

    return [
        'http_code' => $httpCode,
        'response'  => $response
    ];
}
?>