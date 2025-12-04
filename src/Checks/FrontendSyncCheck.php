<?php

declare(strict_types=1);

namespace Bitsoftsolutions\LaravelReverbDoctor\Checks;

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticResult;

class FrontendSyncCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Frontend Sync';
    }

    public function getDescription(): string
    {
        return 'Compare VITE_REVERB_* variables with server-side configuration';
    }

    public function run(): DiagnosticResult
    {
        $mismatches = [];
        $details = [];

        // Server-side values
        $serverHost = env('REVERB_HOST') ?? config('reverb.servers.reverb.host');
        $serverPort = env('REVERB_PORT') ?? config('reverb.servers.reverb.port');
        $serverScheme = env('REVERB_SCHEME') ?? config('reverb.servers.reverb.scheme');
        $serverKey = env('REVERB_APP_KEY') ?? config('reverb.apps.0.key');

        // Frontend values
        $frontendHost = env('VITE_REVERB_HOST');
        $frontendPort = env('VITE_REVERB_PORT');
        $frontendScheme = env('VITE_REVERB_SCHEME');
        $frontendKey = env('VITE_REVERB_APP_KEY');

        $details['server'] = [
            'host' => $serverHost,
            'port' => $serverPort,
            'scheme' => $serverScheme,
            'app_key' => $serverKey ? substr($serverKey, 0, 8) . '...' : null,
        ];

        $details['frontend'] = [
            'VITE_REVERB_HOST' => $frontendHost,
            'VITE_REVERB_PORT' => $frontendPort,
            'VITE_REVERB_SCHEME' => $frontendScheme,
            'VITE_REVERB_APP_KEY' => $frontendKey ? substr($frontendKey, 0, 8) . '...' : null,
        ];

        // Check if frontend variables exist
        $missingFrontend = [];
        if ($frontendHost === null) {
            $missingFrontend[] = 'VITE_REVERB_HOST';
        }
        if ($frontendPort === null) {
            $missingFrontend[] = 'VITE_REVERB_PORT';
        }
        if ($frontendScheme === null) {
            $missingFrontend[] = 'VITE_REVERB_SCHEME';
        }
        if ($frontendKey === null) {
            $missingFrontend[] = 'VITE_REVERB_APP_KEY';
        }

        if (! empty($missingFrontend)) {
            return DiagnosticResult::warn(
                $this->getName(),
                'Missing frontend variables: ' . implode(', ', $missingFrontend),
                $this->generateFrontendSuggestion(),
                $this->verbose ? $details : []
            );
        }

        // Compare values
        if ($frontendHost !== null && $serverHost !== null && $frontendHost !== $serverHost) {
            // Allow localhost/127.0.0.1 equivalence
            if (! $this->isLocalhostEquivalent($frontendHost, $serverHost)) {
                $mismatches[] = "Host: frontend={$frontendHost}, server={$serverHost}";
            }
        }

        if ($frontendPort !== null && $serverPort !== null && (int) $frontendPort !== (int) $serverPort) {
            $mismatches[] = "Port: frontend={$frontendPort}, server={$serverPort}";
        }

        if ($frontendScheme !== null && $serverScheme !== null && $frontendScheme !== $serverScheme) {
            $mismatches[] = "Scheme: frontend={$frontendScheme}, server={$serverScheme}";
        }

        if ($frontendKey !== null && $serverKey !== null && $frontendKey !== $serverKey) {
            $mismatches[] = 'App Key: frontend and server keys do not match';
        }

        if (! empty($mismatches)) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Frontend/server config mismatch',
                "Ensure VITE_REVERB_* variables match server configuration.\nMismatches: " . implode('; ', $mismatches),
                $this->verbose ? array_merge($details, ['mismatches' => $mismatches]) : []
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            'Frontend and server configs are in sync',
            $this->verbose ? $details : []
        );
    }

    protected function isLocalhostEquivalent(string $host1, string $host2): bool
    {
        $localhostAliases = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];

        return in_array($host1, $localhostAliases, true)
            && in_array($host2, $localhostAliases, true);
    }

    protected function generateFrontendSuggestion(): string
    {
        return <<<'SUGGESTION'
Add to your .env file:
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
SUGGESTION;
    }
}
