<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class ConfigConsistencyCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Config Consistency';
    }

    public function getDescription(): string
    {
        return 'Compare .env values against config/reverb.php and config/broadcasting.php';
    }

    public function run(): DiagnosticResult
    {
        $inconsistencies = [];
        $details = [];

        // Check if reverb config exists
        if (! config()->has('reverb')) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Reverb config not found',
                'Run: php artisan vendor:publish --provider="Laravel\\Reverb\\ReverbServiceProvider"'
            );
        }

        // Check reverb.php server configuration
        $reverbHost = config('reverb.servers.reverb.host');
        $reverbPort = config('reverb.servers.reverb.port');
        $reverbScheme = config('reverb.servers.reverb.scheme');

        $envHost = env('REVERB_HOST');
        $envPort = env('REVERB_PORT');
        $envScheme = env('REVERB_SCHEME');

        $details['config_values'] = [
            'reverb.servers.reverb.host' => $reverbHost,
            'reverb.servers.reverb.port' => $reverbPort,
            'reverb.servers.reverb.scheme' => $reverbScheme,
        ];

        $details['env_values'] = [
            'REVERB_HOST' => $envHost,
            'REVERB_PORT' => $envPort,
            'REVERB_SCHEME' => $envScheme,
        ];

        // Compare values
        if ($envHost !== null && $reverbHost !== $envHost) {
            $inconsistencies[] = "REVERB_HOST (env: {$envHost}) != config (reverb.servers.reverb.host: {$reverbHost})";
        }

        if ($envPort !== null && (int) $reverbPort !== (int) $envPort) {
            $inconsistencies[] = "REVERB_PORT (env: {$envPort}) != config (reverb.servers.reverb.port: {$reverbPort})";
        }

        if ($envScheme !== null && $reverbScheme !== $envScheme) {
            $inconsistencies[] = "REVERB_SCHEME (env: {$envScheme}) != config (reverb.servers.reverb.scheme: {$reverbScheme})";
        }

        // Check broadcasting.php reverb connection
        $broadcastingHost = config('broadcasting.connections.reverb.host');
        $broadcastingPort = config('broadcasting.connections.reverb.port');
        $broadcastingScheme = config('broadcasting.connections.reverb.scheme');

        if ($broadcastingHost !== null) {
            $details['broadcasting_values'] = [
                'broadcasting.connections.reverb.host' => $broadcastingHost,
                'broadcasting.connections.reverb.port' => $broadcastingPort,
                'broadcasting.connections.reverb.scheme' => $broadcastingScheme,
            ];

            if ($envHost !== null && $broadcastingHost !== $envHost) {
                $inconsistencies[] = "REVERB_HOST (env: {$envHost}) != broadcasting config (host: {$broadcastingHost})";
            }

            if ($envPort !== null && (int) $broadcastingPort !== (int) $envPort) {
                $inconsistencies[] = "REVERB_PORT (env: {$envPort}) != broadcasting config (port: {$broadcastingPort})";
            }
        }

        if (! empty($inconsistencies)) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Config mismatch detected',
                "Clear config cache: php artisan config:clear\nEnsure .env values match your config files.",
                $this->verbose ? array_merge($details, ['inconsistencies' => $inconsistencies]) : []
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            '.env matches config files',
            $this->verbose ? $details : []
        );
    }
}
