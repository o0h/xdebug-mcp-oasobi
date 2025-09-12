<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'debug:buggy',
    description: 'Command with intentional bugs for debugging practice',
)]
class BuggyCommand extends Command
{
    private array $data = [];
    private ?array $config = null;
    
    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform (null, array, loop, memory, divide, performance)')
            ->addArgument('value', InputArgument::OPTIONAL, 'Optional value for the action');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $value = $input->getArgument('value');
        
        $io->title('Running Buggy Command');
        $io->text("Action: {$action}");
        
        switch ($action) {
            case 'null':
                $this->handleNullError($io);
                break;
                
            case 'array':
                $this->handleArrayError($io, $value);
                break;
                
            case 'loop':
                $this->handleInfiniteLoop($io, $value);
                break;
                
            case 'memory':
                $this->handleMemoryLeak($io);
                break;
                
            case 'divide':
                $this->handleDivisionError($io, $value);
                break;
                
            case 'recursive':
                $this->handleRecursion($io, (int) $value);
                break;
                
            case 'performance':
                $this->handlePerformanceIssue($io, $value);
                break;
                
            default:
                $io->error("Unknown action: {$action}");
                return Command::FAILURE;
        }
        
        $io->success('Command completed!');
        return Command::SUCCESS;
    }
    
    private function handleNullError(SymfonyStyle $io): void
    {
        $io->text('Testing null pointer access...');
        
        $user = $this->fetchUser(999);
        
        $name = $user['name'];
        
        $io->text("User name: {$name}");
        
        $length = strlen($user['email']);
        $io->text("Email length: {$length}");
    }
    
    private function handleArrayError(SymfonyStyle $io, ?string $key): void
    {
        $io->text('Testing array access errors...');
        
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
            'settings' => [
                'theme' => 'dark',
            ],
        ];
        
        $userId = $data['users'][$key]['id'];
        $io->text("User ID: {$userId}");
        
        $preference = $data['settings']['notifications']['email'];
        $io->text("Email notifications: {$preference}");
        
        foreach ($data['products'] as $product) {
            $io->text("Product: {$product['name']}");
        }
    }
    
    private function handleInfiniteLoop(SymfonyStyle $io, ?string $limit): void
    {
        $io->text('Testing loop with condition error...');
        
        $counter = 0;
        $maxLimit = $limit ? (int) $limit : 10;
        
        while ($counter != $maxLimit) {
            $counter += 0.1;
            
            if ($counter > 100) {
                $io->warning('Breaking potential infinite loop');
                break;
            }
            
            if ($counter % 1 == 0) {
                $io->text("Processing: {$counter}");
            }
        }
        
        $io->text("Loop completed at: {$counter}");
    }
    
    private function handleMemoryLeak(SymfonyStyle $io): void
    {
        $io->text('Creating memory leak...');
        
        $leakyArray = [];
        
        for ($i = 0; $i < 1000; $i++) {
            $data = str_repeat('x', 10000);
            
            $leakyArray[] = $data;
            
            $leakyArray[] = &$leakyArray;
            
            if ($i % 100 === 0) {
                $memory = memory_get_usage() / 1024 / 1024;
                $io->text(sprintf('Iteration %d: %.2f MB', $i, $memory));
            }
        }
        
        $io->text('Memory leak test completed');
    }
    
    private function handleDivisionError(SymfonyStyle $io, ?string $divisor): void
    {
        $io->text('Testing division operations...');
        
        $numbers = [100, 50, 25, 10, 5, 0, -5];
        $divideBy = $divisor !== null ? (int) $divisor : 0;
        
        foreach ($numbers as $num) {
            $result = $num / $divideBy;
            $io->text("{$num} / {$divideBy} = {$result}");
            
            $modulo = $num % $divideBy;
            $io->text("{$num} % {$divideBy} = {$modulo}");
        }
    }
    
    private function handleRecursion(SymfonyStyle $io, int $depth): void
    {
        $io->text("Starting recursion with depth: {$depth}");
        
        $result = $this->recursiveFunction($depth);
        
        $io->text("Recursion result: {$result}");
    }
    
    private function recursiveFunction(int $n): int
    {
        if ($n = 0) {
            return 1;
        }
        
        return $n * $this->recursiveFunction($n - 1);
    }
    
    private function handlePerformanceIssue(SymfonyStyle $io, ?string $userCount): void
    {
        $io->text('Testing performance issues with complex data processing...');
        
        $count = $userCount ? (int) $userCount : 20;
        $userIds = range(1, $count);
        
        $userService = new UserService();
        $dataProcessor = new DataProcessor();
        $cacheManager = new CacheManager();
        
        $io->text("Processing {$count} users with advanced algorithms...");
        
        // This looks like efficient batch processing, but actually creates N+1 queries
        $users = $userService->getBatchUsers($userIds);
        
        $totalProcessed = 0;
        foreach ($users as $user) {
            // Complex processing with hidden performance issues
            $processedUser = $dataProcessor->processUser($user, $cacheManager);
            
            // Memory accumulation that looks intentional but isn't cleaned up
            $cacheManager->storeComplexData($user['id'], $processedUser);
            
            $totalProcessed++;
            
            if ($totalProcessed % 5 === 0) {
                $memory = memory_get_usage() / 1024 / 1024;
                $io->text(sprintf('Processed %d users: %.2f MB memory', $totalProcessed, $memory));
            }
        }
        
        $io->text(sprintf('Final cache size: %d entries', $cacheManager->getCacheSize()));
        $io->text(sprintf('Total API calls made: %d', $userService->getTotalCalls()));
    }
    
    private function fetchUser(int $id): ?array
    {
        $users = [
            1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            2 => ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ];
        
        return $users[$id] ?? null;
    }
}

// Complex service classes that demonstrate hidden N+1 and state management issues
class UserService
{
    private int $apiCallCount = 0;
    private array $userCache = [];
    
    public function getBatchUsers(array $userIds): array
    {
        $users = [];
        
        // This looks like batch processing but actually makes individual calls
        foreach ($userIds as $id) {
            // Hidden N+1: Each user requires separate profile and preference lookups
            $user = $this->getUser($id);
            $profile = $this->getUserProfile($id); // +1 query
            $preferences = $this->getUserPreferences($id); // +1 query
            
            $users[] = array_merge($user, [
                'profile' => $profile,
                'preferences' => $preferences
            ]);
        }
        
        return $users;
    }
    
    private function getUser(int $id): array
    {
        $this->apiCallCount++;
        usleep(1000); // Simulate API latency
        
        return [
            'id' => $id,
            'name' => 'User ' . $id,
            'type' => $id % 3 === 0 ? 'premium' : 'standard',
            'created_at' => time() - ($id * 86400)
        ];
    }
    
    private function getUserProfile(int $userId): array
    {
        $this->apiCallCount++;
        usleep(2000); // Simulate heavier API call
        
        return [
            'bio' => str_repeat('Profile data for user ' . $userId . ' ', rand(10, 50)),
            'avatar_url' => 'https://example.com/avatar/' . $userId,
            'settings' => range(1, rand(5, 15)) // Variable complexity
        ];
    }
    
    private function getUserPreferences(int $userId): array
    {
        $this->apiCallCount++;
        usleep(500);
        
        return [
            'theme' => ['dark', 'light'][rand(0, 1)],
            'notifications' => rand(0, 1) === 1,
            'privacy_level' => rand(1, 5)
        ];
    }
    
    public function getTotalCalls(): int
    {
        return $this->apiCallCount;
    }
}

class DataProcessor
{
    private array $processingCache = [];
    private int $totalOperations = 0;
    
    public function processUser(array $user, CacheManager $cache): array
    {
        // Complex nested processing based on user type
        if ($user['type'] === 'premium') {
            return $this->processPremiumUser($user, $cache);
        }
        
        return $this->processStandardUser($user, $cache);
    }
    
    private function processPremiumUser(array $user, CacheManager $cache): array
    {
        $processed = $user;
        
        // Multiple processing stages with state changes
        for ($stage = 1; $stage <= 3; $stage++) {
            for ($item = 0; $item < count($user['profile']['settings']); $item++) {
                $this->totalOperations++;
                
                // Complex computation that changes cache state
                $computationResult = $this->performComplexComputation($user['id'], $stage, $item);
                $cache->updateComputationCache($user['id'], $stage, $computationResult);
                
                // State mutation that's hard to track
                $processed['computed_values'][$stage][$item] = $computationResult;
                
                usleep(100); // Simulate processing time
            }
        }
        
        return $processed;
    }
    
    private function processStandardUser(array $user, CacheManager $cache): array
    {
        $processed = $user;
        
        // Simpler processing but still complex state changes
        for ($i = 0; $i < 2; $i++) {
            $this->totalOperations++;
            $result = $this->performStandardComputation($user['id'], $i);
            $cache->updateComputationCache($user['id'], 1, $result);
            $processed['standard_values'][$i] = $result;
            usleep(50);
        }
        
        return $processed;
    }
    
    private function performComplexComputation(int $userId, int $stage, int $item): array
    {
        // Generate complex data that accumulates memory
        return [
            'result' => str_repeat('computed_data_', rand(100, 1000)),
            'metadata' => range(1, rand(50, 200)),
            'timestamp' => microtime(true),
            'user_id' => $userId,
            'stage' => $stage,
            'item' => $item
        ];
    }
    
    private function performStandardComputation(int $userId, int $iteration): array
    {
        return [
            'result' => str_repeat('standard_', rand(10, 100)),
            'metadata' => range(1, rand(10, 50)),
            'user_id' => $userId,
            'iteration' => $iteration
        ];
    }
}

class CacheManager
{
    private array $cache = [];
    private array $computationCache = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    
    public function storeComplexData(int $userId, array $data): void
    {
        // Memory leak: storing large objects without cleanup
        $this->cache[$userId] = $data;
        
        // Additional memory accumulation
        $this->cache[$userId . '_backup'] = serialize($data);
        $this->cache[$userId . '_metadata'] = [
            'stored_at' => time(),
            'size' => strlen(serialize($data)),
            'type' => $data['type'] ?? 'unknown'
        ];
    }
    
    public function updateComputationCache(int $userId, int $stage, array $result): void
    {
        if (!isset($this->computationCache[$userId])) {
            $this->computationCache[$userId] = [];
        }
        
        if (!isset($this->computationCache[$userId][$stage])) {
            $this->computationCache[$userId][$stage] = [];
        }
        
        // Complex state mutation
        $this->computationCache[$userId][$stage][] = $result;
        
        // Simulate cache efficiency issues
        if (rand(1, 10) > 7) {
            $this->cacheHits++;
        } else {
            $this->cacheMisses++;
        }
    }
    
    public function getCacheSize(): int
    {
        return count($this->cache) + count($this->computationCache);
    }
}