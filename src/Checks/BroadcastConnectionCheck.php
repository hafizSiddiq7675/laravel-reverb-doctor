<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Checks;

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;

class BroadcastConnectionCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Broadcast Connection';
    }

    public function getDescription(): string
    {
        return 'Check BROADCAST_CONNECTION=reverb (critical—defaults to "log")';
    }

    public function run(): DiagnosticResult
    {
        $connection = config('broadcasting.default');
        $envConnection = env('BROADCAST_CONNECTION');
        $deprecatedDriver = env('BROADCAST_DRIVER');

        $details = $this->verbose ? [
            'broadcasting.default' => $connection,
            'BROADCAST_CONNECTION' => $envConnection ?? '(not set)',
            'BROADCAST_DRIVER' => $deprecatedDriver ?? '(not set)',
        ] : [];

        if ($connection === 'log') {
            return DiagnosticResult::fail(
                $this->getName(),
                'Broadcast connection is set to "log" (events not sent)',
                'Set BROADCAST_CONNECTION=reverb in your .env file',
                $details
            );
        }

        if ($connection === 'null' || $connection === null) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Broadcast connection is set to "null" (broadcasting disabled)',
                'Set BROADCAST_CONNECTION=reverb in your .env file',
                $details
            );
        }

        if ($connection !== 'reverb') {
            return DiagnosticResult::warn(
                $this->getName(),
                "Broadcast connection is set to \"{$connection}\" (not reverb)",
                'If you want to use Reverb, set BROADCAST_CONNECTION=reverb in your .env file',
                $details
            );
        }

        // Check if reverb connection is properly configured
        $reverbConnection = config('broadcasting.connections.reverb');
        if ($reverbConnection === null) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Reverb broadcast connection not configured',
                'Ensure broadcasting.connections.reverb is defined in config/broadcasting.php',
                $details
            );
        }

        // Warn about deprecated BROADCAST_DRIVER if it exists alongside BROADCAST_CONNECTION
        if ($deprecatedDriver !== null && $envConnection !== null) {
            return DiagnosticResult::warn(
                $this->getName(),
                'BROADCAST_CONNECTION=reverb (but deprecated BROADCAST_DRIVER also exists)',
                "Remove BROADCAST_DRIVER from your .env file - it's deprecated in Laravel 11+",
                $details
            );
        }

        // Only fail if using BROADCAST_DRIVER without BROADCAST_CONNECTION
        if ($deprecatedDriver !== null && $envConnection === null) {
            return DiagnosticResult::warn(
                $this->getName(),
                "Using deprecated BROADCAST_DRIVER (Laravel 10 style)",
                "Consider migrating to BROADCAST_CONNECTION for Laravel 11+ compatibility",
                $details
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            'BROADCAST_CONNECTION=reverb',
            $details
        );
    }
}
