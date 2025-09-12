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
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform (null, array, loop, memory, divide)')
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
    
    private function fetchUser(int $id): ?array
    {
        $users = [
            1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            2 => ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ];
        
        return $users[$id] ?? null;
    }
}