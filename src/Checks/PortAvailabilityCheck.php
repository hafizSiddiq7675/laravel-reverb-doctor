<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Checks;

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;

class PortAvailabilityCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Port Availability';
    }

    public function getDescription(): string
    {
        return 'Test if configured port (default 8080) is available or already in use';
    }

    public function run(): DiagnosticResult
    {
        $port = (int) (env('REVERB_PORT') ?? config('reverb.servers.reverb.port') ?? 8080);
        $host = env('REVERB_HOST') ?? config('reverb.servers.reverb.host') ?? '0.0.0.0';

        $details = $this->verbose ? [
            'port' => $port,
            'host' => $host,
        ] : [];

        // Check if Reverb is already running on this port
        $reverbRunning = $this->isReverbRunning($port);

        if ($reverbRunning) {
            return DiagnosticResult::pass(
                $this->getName(),
                "Port {$port} in use by Reverb (server running)",
                $details
            );
        }

        // Check if port is available
        $portAvailable = $this->isPortAvailable($host, $port);

        if (! $portAvailable) {
            $processInfo = $this->getProcessUsingPort($port);

            return DiagnosticResult::fail(
                $this->getName(),
                "Port {$port} is already in use",
                $processInfo
                    ? "Port {$port} is used by another process. Either stop that process or change REVERB_PORT in .env"
                    : "Change REVERB_PORT={$port} to an available port in your .env file",
                array_merge($details, ['process_info' => $processInfo])
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            "Port {$port} is available",
            $details
        );
    }

    protected function isPortAvailable(string $host, int $port): bool
    {
        $bindHost = $host === '0.0.0.0' ? '127.0.0.1' : $host;

        $socket = @fsockopen($bindHost, $port, $errno, $errstr, 1);

        if ($socket !== false) {
            fclose($socket);

            return false; // Port is in use (connection successful)
        }

        return true; // Port is available (connection failed)
    }

    protected function isReverbRunning(int $port): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("netstat -ano | findstr :{$port}");

            if ($output) {
                // Check if it's a PHP process (likely Reverb)
                $phpCheck = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul');

                return str_contains($output, 'LISTENING') && $phpCheck !== null;
            }

            return false;
        }

        // Unix-like systems
        $output = shell_exec("lsof -i :{$port} -t 2>/dev/null");

        if (! $output) {
            return false;
        }

        $pid = trim($output);
        $processInfo = shell_exec("ps -p {$pid} -o comm= 2>/dev/null");

        return $processInfo && str_contains($processInfo, 'php');
    }

    protected function getProcessUsingPort(int $port): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("netstat -ano | findstr :{$port} | findstr LISTENING");

            if ($output) {
                preg_match('/\s+(\d+)\s*$/', trim($output), $matches);

                if (! empty($matches[1])) {
                    $pid = $matches[1];
                    $processInfo = shell_exec("tasklist /FI \"PID eq {$pid}\" /FO CSV /NH 2>nul");

                    if ($processInfo) {
                        $parts = str_getcsv(trim($processInfo));

                        return $parts[0] ?? "PID: {$pid}";
                    }

                    return "PID: {$pid}";
                }
            }

            return null;
        }

        // Unix-like systems
        $output = shell_exec("lsof -i :{$port} -t 2>/dev/null");

        if (! $output) {
            return null;
        }

        $pid = trim($output);
        $processInfo = shell_exec("ps -p {$pid} -o comm= 2>/dev/null");

        return $processInfo ? trim($processInfo) . " (PID: {$pid})" : "PID: {$pid}";
    }
}
