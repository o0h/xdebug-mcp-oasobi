<?php

namespace App\Command;

use App\Cache\SimpleCache;
use App\Client\ApiClient;
use App\Service\PostService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'posts:fetch',
    description: 'Fetch posts from JSONPlaceholder API',
)]
class FetchPostCommand extends Command
{
    private PostService $postService;
    
    protected function configure(): void
    {
        $this
            ->addArgument('limit', InputArgument::OPTIONAL, 'Number of posts to fetch', 5)
            ->addOption('with-comments', 'c', InputOption::VALUE_NONE, 'Include comments for each post')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (table, json, simple)', 'table')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'Use cache if available');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $limit = (int) $input->getArgument('limit');
        $withComments = $input->getOption('with-comments');
        $format = $input->getOption('format');
        $useCache = $input->getOption('cache');
        
        $cache = new SimpleCache($useCache ? 300 : 0);
        $client = new ApiClient();
        $this->postService = new PostService($client, $cache);
        
        try {
            $io->title('Fetching Posts from JSONPlaceholder');
            
            if ($withComments) {
                $posts = $this->postService->getPostsWithComments($limit);
            } else {
                $posts = $client->fetchPosts($limit);
            }
            
            if ($format === 'json') {
                $output->writeln(json_encode($posts, JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            }
            
            if ($format === 'simple') {
                foreach ($posts as $post) {
                    $io->section("Post #{$post['id']}");
                    $io->text($post['title']);
                    
                    if ($withComments && isset($post['comment_count'])) {
                        $io->text("Comments: {$post['comment_count']}");
                    }
                }
                return Command::SUCCESS;
            }
            
            $table = new Table($output);
            $headers = ['ID', 'Title', 'User ID'];
            
            if ($withComments) {
                $headers[] = 'Comments';
            }
            
            $table->setHeaders($headers);
            
            foreach ($posts as $post) {
                $row = [
                    $post['id'],
                    substr($post['title'], 0, 50) . (strlen($post['title']) > 50 ? '...' : ''),
                    $post['userId'],
                ];
                
                if ($withComments) {
                    $row[] = $post['comment_count'] ?? 0;
                }
                
                $table->addRow($row);
            }
            
            $table->render();
            
            if ($useCache) {
                $stats = $cache->getStats();
                $io->info(sprintf(
                    'Cache stats: %d hits, %d misses (%.1f%% hit rate)',
                    $stats['hits'],
                    $stats['misses'],
                    $stats['hit_rate']
                ));
            }
            
            $io->success(sprintf('Fetched %d posts successfully!', count($posts)));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Failed to fetch posts: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}