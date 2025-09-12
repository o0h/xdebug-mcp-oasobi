<?php

namespace App\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class ApiClient
{
    private Client $httpClient;
    private array $lastResponse = [];
    private int $requestCount = 0;
    private array $requestHistory = [];
    
    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 10,
            'allow_redirects' => true,
        ]);
    }
    
    public function fetchPosts(int $limit = 10): array
    {
        $this->requestCount++;
        $url = 'https://jsonplaceholder.typicode.com/posts';
        
        try {
            $response = $this->httpClient->get($url, [
                'query' => ['_limit' => $limit],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data == null) {
                return [];
            }
            
            $this->lastResponse = $data;
            $this->requestHistory[] = [
                'url' => $url,
                'method' => 'GET',
                'timestamp' => time(),
                'response_size' => strlen($response->getBody()),
            ];
            
            return $data;
            
        } catch (RequestException $e) {
            throw new \RuntimeException('Failed to fetch posts: ' . $e->getMessage());
        }
    }
    
    public function fetchPost(int $id): ?array
    {
        $this->requestCount++;
        $url = "https://jsonplaceholder.typicode.com/posts/{$id}";
        
        try {
            $response = $this->httpClient->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->lastResponse = $data;
            $this->requestHistory[] = [
                'url' => $url,
                'method' => 'GET',
                'timestamp' => time(),
            ];
            
            return $data;
            
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                return null;
            }
            throw new \RuntimeException('Failed to fetch post: ' . $e->getMessage());
        }
    }
    
    public function fetchComments(int $postId): array
    {
        $this->requestCount++;
        $url = 'https://jsonplaceholder.typicode.com/comments';
        
        try {
            $response = $this->httpClient->get($url, [
                'query' => ['postId' => $postId],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->lastResponse = $data;
            
            return $data ?: [];
            
        } catch (RequestException $e) {
            throw new \RuntimeException('Failed to fetch comments: ' . $e->getMessage());
        }
    }
    
    public function fetchUsers(): array
    {
        $this->requestCount++;
        $url = 'https://jsonplaceholder.typicode.com/users';
        
        try {
            $response = $this->httpClient->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->lastResponse = $data;
            
            return $data ?: [];
            
        } catch (RequestException $e) {
            throw new \RuntimeException('Failed to fetch users: ' . $e->getMessage());
        }
    }
    
    public function testHttpBin(string $method = 'GET', array $data = []): array
    {
        $this->requestCount++;
        $url = 'https://httpbin.org/' . strtolower($method);
        
        try {
            $options = [];
            
            if ($method === 'POST' || $method === 'PUT') {
                $options['json'] = $data;
            }
            
            $response = $this->httpClient->request($method, $url, $options);
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->lastResponse = $responseData;
            
            return $responseData;
            
        } catch (RequestException $e) {
            throw new \RuntimeException('HTTP test failed: ' . $e->getMessage());
        }
    }
    
    public function testDelay(int $seconds): array
    {
        $this->requestCount++;
        
        if ($seconds > 10) {
            $seconds = 10;
        }
        
        $url = "https://httpbin.org/delay/{$seconds}";
        
        try {
            $start = microtime(true);
            $response = $this->httpClient->get($url);
            $elapsed = microtime(true) - $start;
            
            $data = json_decode($response->getBody()->getContents(), true);
            $data['actual_delay'] = $elapsed;
            
            $this->lastResponse = $data;
            
            return $data;
            
        } catch (RequestException $e) {
            throw new \RuntimeException('Delay test failed: ' . $e->getMessage());
        }
    }
    
    public function getRequestCount(): int
    {
        return $this->requestCount;
    }
    
    public function getLastResponse(): array
    {
        return $this->lastResponse;
    }
    
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }
}