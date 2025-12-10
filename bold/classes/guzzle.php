<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class BoldGuzzle
{
    private $client;

    public function __construct($baseUri)
    {
        $this->client = new Client(['base_uri' => $baseUri]);
    }

    public function post($endpoint, $data)
    {
        try
        {
            $response = $this->client->post($endpoint, [
                'form_params' => $data
            ]);

            // Obtener el cuerpo de la respuesta
            $body = $response->getBody();
            $responseData = json_decode($body, true);

            return $responseData;
        }
        catch (RequestException $e)
        {
            // Manejar errores
            if ($e->hasResponse())
            {
                return [
                    'error' => true,
                    'message' => $e->getResponse()->getReasonPhrase(),
                    'status_code' => $e->getResponse()->getStatusCode()
                ];
            }
            else
            {
                return [
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            }
        }
    }
}