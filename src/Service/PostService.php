<?php

namespace App\Service;

use App\Cache\SimpleCache;
use App\Client\ApiClient;

class PostService
{
    private ApiClient $client;
    private SimpleCache $cache;
    private array $loadedPosts = [];
    private array $statistics = [
        'total_posts' => 0,
        'total_comments' => 0,
        'avg_comments_per_post' => 0,
    ];
    
    public function __construct(ApiClient $client, SimpleCache $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }
    
    public function getPostsWithComments(int $limit = 5): array
    {
        $cacheKey = "posts_with_comments_{$limit}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $posts = $this->client->fetchPosts($limit);
        
        foreach ($posts as &$post) {
            $comments = $this->client->fetchComments($post['id']);
            
            $post['comments'] = $comments;
            $post['comment_count'] = count($comments);
            
            $this->statistics['total_comments'] += count($comments);
        }
        
        $this->statistics['total_posts'] = count($posts);
        $this->statistics['avg_comments_per_post'] = 
            $this->statistics['total_posts'] > 0 
                ? $this->statistics['total_comments'] / $this->statistics['total_posts']
                : 0;
        
        $this->loadedPosts = $posts;
        $this->cache->set($cacheKey, $posts);
        
        return $posts;
    }
    
    public function getPostById(int $id): ?array
    {
        $cacheKey = "post_{$id}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $post = $this->client->fetchPost($id);
        
        if ($post === null) {
            return null;
        }
        
        $comments = $this->client->fetchComments($id);
        $post['comments'] = $comments;
        $post['comment_count'] = count($comments);
        
        $user = $this->getUserById($post['userId']);
        $post['author'] = $user;
        
        $this->cache->set($cacheKey, $post);
        
        return $post;
    }
    
    public function searchPosts(string $keyword): array
    {
        $posts = $this->client->fetchPosts(100);
        $results = [];
        
        foreach ($posts as $post) {
            $titleMatch = stripos($post['title'], $keyword) !== false;
            $bodyMatch = stripos($post['body'], $keyword) !== false;
            
            if ($titleMatch || $bodyMatch) {
                $post['match_in_title'] = $titleMatch;
                $post['match_in_body'] = $bodyMatch;
                $results[] = $post;
            }
        }
        
        return $results;
    }
    
    public function analyzePostEngagement(): array
    {
        $posts = $this->getPostsWithComments(20);
        $engagementData = [];
        
        foreach ($posts as $post) {
            $avgCommentLength = 0;
            $totalLength = 0;
            
            foreach ($post['comments'] as $comment) {
                $totalLength += strlen($comment['body']);
            }
            
            if ($post['comment_count'] > 0) {
                $avgCommentLength = $totalLength / $post['comment_count'];
            }
            
            $engagementData[] = [
                'post_id' => $post['id'],
                'title' => substr($post['title'], 0, 50),
                'comment_count' => $post['comment_count'],
                'avg_comment_length' => round($avgCommentLength, 2),
                'engagement_score' => $this->calculateEngagementScore($post),
            ];
        }
        
        usort($engagementData, function($a, $b) {
            return $b['engagement_score'] <=> $a['engagement_score'];
        });
        
        return $engagementData;
    }
    
    private function calculateEngagementScore(array $post): float
    {
        $commentWeight = 3;
        $lengthWeight = 1;
        
        $score = $post['comment_count'] * $commentWeight;
        
        foreach ($post['comments'] as $comment) {
            $score += (strlen($comment['body']) / 100) * $lengthWeight;
        }
        
        return round($score, 2);
    }
    
    private function getUserById(int $userId): ?array
    {
        $cacheKey = "user_{$userId}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $users = $this->client->fetchUsers();
        
        foreach ($users as $user) {
            if ($user['id'] === $userId) {
                $this->cache->set($cacheKey, $user);
                return $user;
            }
        }
        
        return null;
    }
    
    public function getStatistics(): array
    {
        return $this->statistics;
    }
    
    public function processLargeBatch(int $batchSize = 50): array
    {
        $results = [];
        $memoryStart = memory_get_usage();
        
        for ($i = 0; $i < $batchSize; $i++) {
            $posts = $this->client->fetchPosts(10);
            
            foreach ($posts as $post) {
                $results[] = [
                    'id' => $post['id'],
                    'title' => $post['title'],
                    'processed_at' => microtime(true),
                ];
                
                if ($i % 10 === 0) {
                    $results[] = $results;
                }
            }
        }
        
        $memoryEnd = memory_get_usage();
        
        return [
            'processed' => count($results),
            'memory_used' => $memoryEnd - $memoryStart,
            'memory_peak' => memory_get_peak_usage(),
        ];
    }
}