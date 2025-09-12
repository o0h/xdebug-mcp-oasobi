<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class WeatherApiClient
{
    private Client $httpClient;
    private array $config;
    private ?array $lastResponse = null;
    private int $requestCount = 0;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config['base_url'],
            'timeout' => $config['timeout'],
        ]);
    }
    
    public function getCurrentWeather(string $city): array
    {
        $this->requestCount++;
        
        $params = [
            'q' => $city,
            'appid' => $this->config['api_key'],
            'units' => 'metric',
        ];
        
        $retryCount = 0;
        $lastException = null;
        
        while ($retryCount < $this->config['retry_limit']) {
            try {
                $response = $this->httpClient->get('weather', [
                    'query' => $params,
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                if ($data === null) {
                    throw new \RuntimeException('Invalid JSON response');
                }
                
                $this->lastResponse = $data;
                
                if (!isset($data['main'])) {
                    $data['main'] = null;
                }
                
                return $data;
                
            } catch (RequestException $e) {
                $lastException = $e;
                $retryCount++;
                
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                    throw new \InvalidArgumentException("City '{$city}' not found");
                }
                
                if ($retryCount >= $this->config['retry_limit']) {
                    throw new \RuntimeException(
                        "API request failed after {$retryCount} attempts: " . $e->getMessage(),
                        0,
                        $e
                    );
                }
                
                usleep(500000 * $retryCount);
            } catch (GuzzleException $e) {
                throw new \RuntimeException('HTTP request failed: ' . $e->getMessage(), 0, $e);
            }
        }
        
        throw $lastException ?? new \RuntimeException('Unknown error occurred');
    }
    
    public function getForecast(string $city, int $days = 5): array
    {
        $this->requestCount++;
        
        $params = [
            'q' => $city,
            'appid' => $this->config['api_key'],
            'units' => 'metric',
            'cnt' => $days * 8,
        ];
        
        try {
            $response = $this->httpClient->get('forecast', [
                'query' => $params,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('JSON decode error: ' . json_last_error_msg());
            }
            
            $this->lastResponse = $data;
            
            return $data;
            
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                throw new \InvalidArgumentException("City '{$city}' not found");
            }
            
            throw new \RuntimeException('API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }
    
    public function getRequestCount(): int
    {
        return $this->requestCount;
    }
}