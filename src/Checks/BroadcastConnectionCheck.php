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

        $details = $this->verbose ? [
            'broadcasting.default' => $connection,
            'BROADCAST_CONNECTION' => $envConnection ?? '(not set)',
        ] : [];

        // Check for deprecated BROADCAST_DRIVER
        $deprecatedDriver = env('BROADCAST_DRIVER');
        if ($deprecatedDriver !== null) {
            return DiagnosticResult::fail(
                $this->getName(),
                "Using deprecated BROADCAST_DRIVER={$deprecatedDriver}",
                "Replace BROADCAST_DRIVER with BROADCAST_CONNECTION in your .env file:\nBROADCAST_CONNECTION=reverb",
                array_merge($details, ['deprecated' => 'BROADCAST_DRIVER is deprecated in Laravel 11+'])
            );
        }

        if ($connection === 'log') {
            return DiagnosticResult::fail(
                $this->getName(),
                'Broadcast connection is set to "log" (events not sent)',
                'Set BROADCAST_CONNECTION=reverb in your .env file',
                $details
            );
        }

        if ($connection === 'null') {
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

        return DiagnosticResult::pass(
            $this->getName(),
            'BROADCAST_CONNECTION=reverb',
            $details
        );
    }
}
