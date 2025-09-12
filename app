#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Command\AnalyzeCommand;
use App\Command\BuggyCommand;
use App\Command\FetchPostCommand;
use App\Command\TestHttpCommand;
use Symfony\Component\Console\Application;

$application = new Application('API Test Tool', '1.0.0');

$application->add(new FetchPostCommand());
$application->add(new AnalyzeCommand());
$application->add(new TestHttpCommand());
$application->add(new BuggyCommand());

$application->run();