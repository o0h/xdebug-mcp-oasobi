<?php

namespace App\Command;

use App\Client\ApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'http:test',
    description: 'Test HTTP operations using httpbin.org',
)]
class TestHttpCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('method', InputArgument::OPTIONAL, 'HTTP method to test', 'GET')
            ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'Test delay endpoint (seconds)', null)
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'JSON data to send', '{}')
            ->addOption('benchmark', 'b', InputOption::VALUE_NONE, 'Run performance benchmark');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $method = strtoupper($input->getArgument('method'));
        $delay = $input->getOption('delay');
        $data = json_decode($input->getOption('data'), true) ?? [];
        $benchmark = $input->getOption('benchmark');
        
        $client = new ApiClient();
        
        try {
            if ($benchmark) {
                $io->title('Running HTTP Benchmark');
                
                $methods = ['GET', 'POST', 'PUT', 'DELETE'];
                $results = [];
                
                foreach ($methods as $testMethod) {
                    $io->section("Testing {$testMethod}");
                    
                    $times = [];
                    for ($i = 0; $i < 5; $i++) {
                        $start = microtime(true);
                        $client->testHttpBin($testMethod, ['test' => $i]);
                        $elapsed = microtime(true) - $start;
                        $times[] = $elapsed;
                        
                        $io->text(sprintf('  Request %d: %.3f seconds', $i + 1, $elapsed));
                    }
                    
                    $avg = array_sum($times) / count($times);
                    $results[$testMethod] = [
                        'avg' => $avg,
                        'min' => min($times),
                        'max' => max($times),
                    ];
                }
                
                $io->section('Benchmark Results');
                foreach ($results as $method => $stats) {
                    $io->text(sprintf(
                        '%s: Avg=%.3fs, Min=%.3fs, Max=%.3fs',
                        $method,
                        $stats['avg'],
                        $stats['min'],
                        $stats['max']
                    ));
                }
                
                return Command::SUCCESS;
            }
            
            if ($delay !== null) {
                $io->title("Testing Delay Endpoint ({$delay} seconds)");
                
                $io->text('Sending request...');
                $start = microtime(true);
                
                $response = $client->testDelay((int) $delay);
                
                $io->section('Response');
                $io->text(sprintf('Requested Delay: %d seconds', $delay));
                $io->text(sprintf('Actual Delay: %.3f seconds', $response['actual_delay']));
                
                if (isset($response['args'])) {
                    $io->text('Response Data: ' . json_encode($response['args']));
                }
                
                return Command::SUCCESS;
            }
            
            $io->title("Testing HTTP {$method}");
            
            $io->text(sprintf('Sending %s request to httpbin.org...', $method));
            
            $response = $client->testHttpBin($method, $data);
            
            $io->section('Response');
            
            if (isset($response['headers'])) {
                $io->text('Headers:');
                foreach ($response['headers'] as $header => $value) {
                    $io->text("  {$header}: {$value}");
                }
            }
            
            if (isset($response['json']) && !empty($response['json'])) {
                $io->text('');
                $io->text('Sent Data:');
                $io->text(json_encode($response['json'], JSON_PRETTY_PRINT));
            }
            
            if (isset($response['origin'])) {
                $io->text('');
                $io->text('Origin IP: ' . $response['origin']);
            }
            
            if (isset($response['url'])) {
                $io->text('URL: ' . $response['url']);
            }
            
            $io->success('HTTP test completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('HTTP test failed: ' . $e->getMessage());
            
            if ($output->isVerbose()) {
                $io->section('Debug Information');
                $io->text('Exception: ' . get_class($e));
                $io->text('File: ' . $e->getFile() . ':' . $e->getLine());
                $io->text('Trace:');
                $io->text($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
}