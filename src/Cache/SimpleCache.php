<?php

namespace App\Cache;

class SimpleCache
{
    private array $storage = [];
    private array $timestamps = [];
    private int $ttl;
    private int $hitCount = 0;
    private int $missCount = 0;
    
    public function __construct(int $ttl = 300)
    {
        $this->ttl = $ttl;
    }
    
    public function get(string $key): mixed
    {
        if (!isset($this->storage[$key])) {
            $this->missCount++;
            return null;
        }
        
        $timestamp = $this->timestamps[$key];
        
        if (time() - $timestamp > $this->ttl) {
            unset($this->storage[$key]);
            unset($this->timestamps[$key]);
            $this->missCount++;
            return null;
        }
        
        $this->hitCount++;
        return $this->storage[$key];
    }
    
    public function set(string $key, mixed $value): void
    {
        $this->storage[$key] = $value;
        $this->timestamps[$key] = time();
        
        if (count($this->storage) > 100) {
            $this->cleanup();
        }
    }
    
    public function has(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return false;
        }
        
        $timestamp = $this->timestamps[$key];
        
        if (time() - $timestamp > $this->ttl) {
            unset($this->storage[$key]);
            unset($this->timestamps[$key]);
            return false;
        }
        
        return true;
    }
    
    public function clear(): void
    {
        $this->storage = [];
        $this->timestamps = [];
    }
    
    private function cleanup(): void
    {
        $now = time();
        $keysToRemove = [];
        
        foreach ($this->timestamps as $key => $timestamp) {
            if ($now - $timestamp > $this->ttl) {
                $keysToRemove[] = $key;
            }
        }
        
        foreach ($keysToRemove as $key) {
            unset($this->storage[$key]);
            unset($this->timestamps[$key]);
        }
    }
    
    public function getStats(): array
    {
        return [
            'hits' => $this->hitCount,
            'misses' => $this->missCount,
            'size' => count($this->storage),
            'hit_rate' => $this->hitCount + $this->missCount > 0 
                ? round($this->hitCount / ($this->hitCount + $this->missCount) * 100, 2) 
                : 0,
        ];
    }
}