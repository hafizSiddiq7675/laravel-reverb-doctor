<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class ReverbProcessCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Reverb Process';
    }

    public function getDescription(): string
    {
        return 'Detect if php artisan reverb:start is currently running';
    }

    public function run(): DiagnosticResult
    {
        $port = (int) (env('REVERB_PORT') ?? config('reverb.servers.reverb.port') ?? 8080);
        $isRunning = $this->isReverbProcessRunning();
        $isListening = $this->isPortListening($port);

        $details = $this->verbose ? [
            'port' => $port,
            'process_detected' => $isRunning,
            'port_listening' => $isListening,
        ] : [];

        if ($isRunning || $isListening) {
            return DiagnosticResult::pass(
                $this->getName(),
                'Reverb server is running',
                $details
            );
        }

        return DiagnosticResult::warn(
            $this->getName(),
            'Reverb is not running',
            'Start Reverb with: php artisan reverb:start',
            $details
        );
    }

    protected function isReverbProcessRunning(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: check for php process with reverb:start
            $output = shell_exec('wmic process where "name=\'php.exe\'" get commandline 2>nul');

            if ($output && str_contains(strtolower($output), 'reverb:start')) {
                return true;
            }

            // Alternative: check using tasklist
            $output = shell_exec('tasklist /v /fi "imagename eq php.exe" 2>nul');

            return $output && str_contains(strtolower($output), 'reverb');
        }

        // Unix-like systems
        $output = shell_exec('pgrep -f "reverb:start" 2>/dev/null');

        if (! empty(trim($output ?? ''))) {
            return true;
        }

        // Alternative: ps aux grep
        $output = shell_exec('ps aux 2>/dev/null | grep -v grep | grep "reverb:start"');

        return ! empty(trim($output ?? ''));
    }

    protected function isPortListening(int $port): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("netstat -ano | findstr :{$port} | findstr LISTENING");

            return ! empty(trim($output ?? ''));
        }

        // Unix-like: check if port is listening
        $output = shell_exec("lsof -i :{$port} -sTCP:LISTEN 2>/dev/null");

        if (! empty(trim($output ?? ''))) {
            return true;
        }

        // Alternative: netstat
        $output = shell_exec("netstat -tln 2>/dev/null | grep :{$port}");

        return ! empty(trim($output ?? ''));
    }
}
