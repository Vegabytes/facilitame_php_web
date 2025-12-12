<?php
/**
 * Cliente para Google Calendar API
 * Maneja OAuth y operaciones CRUD de eventos
 */
class GoogleCalendarClient
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $accessToken;
    private $refreshToken;
    private $userId;
    private $pdo;

    const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';
    const SCOPES = 'https://www.googleapis.com/auth/calendar.events';

    public function __construct($userId = null)
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->clientId = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri = GOOGLE_REDIRECT_URI;
        $this->userId = $userId;

        if ($userId) {
            $this->loadTokens();
        }
    }

    /**
     * Genera la URL para iniciar el flujo OAuth
     */
    public function getAuthUrl($state = null)
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por tokens
     */
    public function exchangeCode($code)
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (isset($response['error'])) {
            throw new Exception('Error al obtener tokens: ' . ($response['error_description'] ?? $response['error']));
        }

        return $response;
    }

    /**
     * Guarda los tokens en la base de datos
     */
    public function saveTokens($userId, $tokens)
    {
        $expiresAt = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));

        $stmt = $this->pdo->prepare("
            INSERT INTO user_google_calendar (user_id, access_token, refresh_token, token_expires_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
                token_expires_at = VALUES(token_expires_at),
                is_active = 1,
                updated_at = NOW()
        ");
        $stmt->execute([
            $userId,
            $tokens['access_token'],
            $tokens['refresh_token'] ?? null,
            $expiresAt
        ]);

        $this->accessToken = $tokens['access_token'];
        $this->refreshToken = $tokens['refresh_token'] ?? $this->refreshToken;
        $this->userId = $userId;
    }

    /**
     * Carga los tokens desde la base de datos
     */
    private function loadTokens()
    {
        $stmt = $this->pdo->prepare("
            SELECT access_token, refresh_token, token_expires_at
            FROM user_google_calendar
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$this->userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->accessToken = $row['access_token'];
            $this->refreshToken = $row['refresh_token'];

            // Refrescar si está expirado
            if (strtotime($row['token_expires_at']) < time()) {
                $this->refreshAccessToken();
            }
        }
    }

    /**
     * Refresca el access token usando el refresh token
     */
    private function refreshAccessToken()
    {
        if (!$this->refreshToken) {
            throw new Exception('No hay refresh token disponible');
        }

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (isset($response['error'])) {
            // Token inválido, desactivar la conexión
            $this->disconnect();
            throw new Exception('Sesión de Google expirada. Reconecta tu cuenta.');
        }

        $this->saveTokens($this->userId, $response);
    }

    /**
     * Verifica si el usuario tiene Google Calendar conectado
     */
    public function isConnected()
    {
        return !empty($this->accessToken);
    }

    /**
     * Desconecta Google Calendar del usuario
     */
    public function disconnect()
    {
        $stmt = $this->pdo->prepare("
            UPDATE user_google_calendar
            SET is_active = 0, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);

        $this->accessToken = null;
        $this->refreshToken = null;
    }

    /**
     * Crea un evento en Google Calendar
     */
    public function createEvent($summary, $description, $startDateTime, $endDateTime, $location = null)
    {
        if (!$this->isConnected()) {
            throw new Exception('Google Calendar no está conectado');
        }

        $event = [
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $this->formatDateTime($startDateTime),
                'timeZone' => 'Europe/Madrid',
            ],
            'end' => [
                'dateTime' => $this->formatDateTime($endDateTime),
                'timeZone' => 'Europe/Madrid',
            ],
        ];

        if ($location) {
            $event['location'] = $location;
        }

        $response = $this->apiRequest('POST', '/calendars/primary/events', $event);

        return $response['id'] ?? null;
    }

    /**
     * Actualiza un evento existente
     */
    public function updateEvent($eventId, $summary, $description, $startDateTime, $endDateTime, $location = null)
    {
        if (!$this->isConnected()) {
            throw new Exception('Google Calendar no está conectado');
        }

        $event = [
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $this->formatDateTime($startDateTime),
                'timeZone' => 'Europe/Madrid',
            ],
            'end' => [
                'dateTime' => $this->formatDateTime($endDateTime),
                'timeZone' => 'Europe/Madrid',
            ],
        ];

        if ($location) {
            $event['location'] = $location;
        }

        $response = $this->apiRequest('PUT', '/calendars/primary/events/' . $eventId, $event);

        return $response['id'] ?? null;
    }

    /**
     * Elimina un evento
     */
    public function deleteEvent($eventId)
    {
        if (!$this->isConnected()) {
            throw new Exception('Google Calendar no está conectado');
        }

        $this->apiRequest('DELETE', '/calendars/primary/events/' . $eventId);

        return true;
    }

    /**
     * Obtiene los calendarios del usuario
     */
    public function listCalendars()
    {
        if (!$this->isConnected()) {
            throw new Exception('Google Calendar no está conectado');
        }

        return $this->apiRequest('GET', '/users/me/calendarList');
    }

    /**
     * Realiza una petición a la API de Google Calendar
     */
    private function apiRequest($method, $endpoint, $data = null)
    {
        $url = self::CALENDAR_API . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // DELETE exitoso retorna 204 sin body
        if ($method === 'DELETE' && $httpCode === 204) {
            return true;
        }

        $result = json_decode($response, true);

        if ($httpCode === 401) {
            // Token expirado, intentar refrescar
            $this->refreshAccessToken();
            return $this->apiRequest($method, $endpoint, $data);
        }

        if ($httpCode >= 400) {
            throw new Exception($result['error']['message'] ?? 'Error en la API de Google Calendar');
        }

        return $result;
    }

    /**
     * Petición HTTP POST simple
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Formatea datetime para Google Calendar
     */
    private function formatDateTime($datetime)
    {
        if ($datetime instanceof DateTime) {
            return $datetime->format('c');
        }
        return date('c', strtotime($datetime));
    }
}
