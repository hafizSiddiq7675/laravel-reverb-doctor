<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class QueueWorkerCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Queue Worker';
    }

    public function getDescription(): string
    {
        return 'Check if queue worker is running (required for ShouldBroadcast events)';
    }

    public function run(): DiagnosticResult
    {
        $queueConnection = config('queue.default');
        $details = $this->verbose ? ['queue_connection' => $queueConnection] : [];

        // If using sync driver, queue worker not needed
        if ($queueConnection === 'sync') {
            return DiagnosticResult::pass(
                $this->getName(),
                'Using sync queue (no worker needed)',
                $details
            );
        }

        $isRunning = $this->isQueueWorkerRunning();
        $details['worker_detected'] = $isRunning;

        if (! $isRunning) {
            return DiagnosticResult::warn(
                $this->getName(),
                'Queue worker not detected',
                "Start a queue worker with: php artisan queue:work\nBroadcast events using ShouldBroadcast require a running queue worker.",
                $details
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            'Queue worker is running',
            $details
        );
    }

    protected function isQueueWorkerRunning(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: check for php process with queue:work or queue:listen
            $output = shell_exec('wmic process where "name=\'php.exe\'" get commandline 2>nul');

            if ($output) {
                $lowerOutput = strtolower($output);

                return str_contains($lowerOutput, 'queue:work')
                    || str_contains($lowerOutput, 'queue:listen')
                    || str_contains($lowerOutput, 'horizon');
            }

            return false;
        }

        // Unix-like systems
        $patterns = ['queue:work', 'queue:listen', 'horizon'];

        foreach ($patterns as $pattern) {
            $output = shell_exec("pgrep -f '{$pattern}' 2>/dev/null");

            if (! empty(trim($output ?? ''))) {
                return true;
            }
        }

        // Alternative: ps aux grep
        $output = shell_exec('ps aux 2>/dev/null | grep -E "queue:(work|listen)|horizon" | grep -v grep');

        return ! empty(trim($output ?? ''));
    }
}
