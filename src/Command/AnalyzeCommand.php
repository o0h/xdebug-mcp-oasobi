<?php

namespace App\Command;

use App\Cache\SimpleCache;
use App\Client\ApiClient;
use App\Service\PostService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'posts:analyze',
    description: 'Analyze post engagement and statistics',
)]
class AnalyzeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('posts', 'p', InputOption::VALUE_REQUIRED, 'Number of posts to analyze', 20)
            ->addOption('memory-test', 'm', InputOption::VALUE_NONE, 'Run memory leak test')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Batch size for memory test', 50);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $postCount = (int) $input->getOption('posts');
        $memoryTest = $input->getOption('memory-test');
        $batchSize = (int) $input->getOption('batch-size');
        
        $cache = new SimpleCache(300);
        $client = new ApiClient();
        $postService = new PostService($client, $cache);
        
        try {
            if ($memoryTest) {
                $io->title('Running Memory Test');
                
                $progressBar = new ProgressBar($output, $batchSize);
                $progressBar->start();
                
                $oldProgress = 0;
                for ($i = 1; $i <= $batchSize; $i++) {
                    $result = $postService->processLargeBatch(1);
                    $progressBar->advance();
                    
                    if ($i % 10 === 0) {
                        $progressBar->setMessage(sprintf(
                            'Memory: %.2f MB',
                            memory_get_usage() / 1024 / 1024
                        ));
                    }
                }
                
                $progressBar->finish();
                $io->newLine(2);
                
                $io->section('Memory Usage Report');
                $io->listing([
                    sprintf('Peak Memory: %.2f MB', memory_get_peak_usage() / 1024 / 1024),
                    sprintf('Current Memory: %.2f MB', memory_get_usage() / 1024 / 1024),
                    sprintf('Requests Made: %d', $client->getRequestCount()),
                ]);
                
                return Command::SUCCESS;
            }
            
            $io->title('Analyzing Post Engagement');
            
            $progressBar = new ProgressBar($output, 3);
            $progressBar->start();
            
            $progressBar->setMessage('Fetching posts...');
            $progressBar->advance();
            
            $posts = $postService->getPostsWithComments($postCount);
            
            $progressBar->setMessage('Calculating engagement...');
            $progressBar->advance();
            
            $engagementData = $postService->analyzePostEngagement();
            
            $progressBar->setMessage('Generating report...');
            $progressBar->advance();
            
            $progressBar->finish();
            $io->newLine(2);
            
            $io->section('Top Engaged Posts');
            
            $table = new Table($output);
            $table->setHeaders(['Rank', 'Post ID', 'Title', 'Comments', 'Avg Length', 'Score']);
            
            $rank = 1;
            foreach (array_slice($engagementData, 0, 10) as $data) {
                $table->addRow([
                    $rank++,
                    $data['post_id'],
                    $data['title'] . '...',
                    $data['comment_count'],
                    $data['avg_comment_length'],
                    $data['engagement_score'],
                ]);
            }
            
            $table->render();
            
            $stats = $postService->getStatistics();
            $io->section('Overall Statistics');
            $io->listing([
                sprintf('Total Posts Analyzed: %d', $stats['total_posts']),
                sprintf('Total Comments: %d', $stats['total_comments']),
                sprintf('Average Comments per Post: %.2f', $stats['avg_comments_per_post']),
                sprintf('API Requests Made: %d', $client->getRequestCount()),
            ]);
            
            $cacheStats = $cache->getStats();
            if ($cacheStats['hits'] + $cacheStats['misses'] > 0) {
                $io->section('Cache Performance');
                $io->listing([
                    sprintf('Cache Hits: %d', $cacheStats['hits']),
                    sprintf('Cache Misses: %d', $cacheStats['misses']),
                    sprintf('Hit Rate: %.1f%%', $cacheStats['hit_rate']),
                    sprintf('Cached Items: %d', $cacheStats['size']),
                ]);
            }
            
            $io->success('Analysis completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Analysis failed: ' . $e->getMessage());
            
            if ($output->isVerbose()) {
                $io->section('Stack Trace');
                $io->text($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }
}