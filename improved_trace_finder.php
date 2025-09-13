<?php
/**
 * Improved trace file finder - proof of concept for MCP tool enhancement
 * 
 * This demonstrates how to dynamically read xdebug.trace_output_name
 * and construct the appropriate glob pattern for finding trace files.
 */

function findTraceFiles(string $xdebugOutputDir): array
{
    // Get current trace_output_name setting (same approach as xdebug.output_dir)
    $traceOutputName = ini_get('xdebug.trace_output_name') ?: 'trace.%c';
    
    echo "📋 Current xdebug.trace_output_name: {$traceOutputName}\n";
    
    // Convert Xdebug format specifiers to glob wildcards
    // %c=CRC32, %p=PID, %r=Random, %s=Script, %t=Timestamp, %u=Microseconds, etc.
    $filePattern = preg_replace('/%(c|p|r|s|t|u|H|R|U|S)/', '*', $traceOutputName);
    
    echo "🔍 Generated glob pattern: {$xdebugOutputDir}/{$filePattern}.xt\n";
    
    // Find trace files using dynamic pattern
    $traceFiles = glob("{$xdebugOutputDir}/{$filePattern}.xt");
    
    if (empty($traceFiles)) {
        echo "❌ No trace files found with pattern: {$filePattern}.xt\n";
        return [];
    }
    
    // Sort by modification time (newest first)
    usort($traceFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    echo "✅ Found " . count($traceFiles) . " trace file(s)\n";
    echo "📁 Most recent: " . basename($traceFiles[0]) . "\n";
    
    return $traceFiles;
}

// Test the function
$xdebugOutputDir = ini_get('xdebug.output_dir') ?: '/tmp';
echo "🎯 Testing improved trace file finder\n";
echo "📂 Output directory: {$xdebugOutputDir}\n";

$files = findTraceFiles($xdebugOutputDir);

if (!empty($files)) {
    echo "🎉 Success! Dynamic pattern matching works.\n";
    echo "📄 Latest file: {$files[0]}\n";
} else {
    echo "⚠️  No trace files found (run a trace first)\n";
}