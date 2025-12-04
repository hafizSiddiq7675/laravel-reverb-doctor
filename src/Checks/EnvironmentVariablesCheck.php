<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class EnvironmentVariablesCheck extends BaseCheck
{
    protected array $requiredServerVars = [
        'REVERB_APP_ID',
        'REVERB_APP_KEY',
        'REVERB_APP_SECRET',
        'REVERB_HOST',
        'REVERB_PORT',
        'REVERB_SCHEME',
    ];

    protected array $requiredFrontendVars = [
        'VITE_REVERB_APP_KEY',
        'VITE_REVERB_HOST',
        'VITE_REVERB_PORT',
        'VITE_REVERB_SCHEME',
    ];

    public function getName(): string
    {
        return 'Environment Variables';
    }

    public function getDescription(): string
    {
        return 'Verify all required REVERB_* and VITE_REVERB_* variables exist in .env';
    }

    public function run(): DiagnosticResult
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return DiagnosticResult::fail(
                $this->getName(),
                '.env file not found',
                'Create a .env file by copying .env.example'
            );
        }

        $envContent = file_get_contents($envPath);
        $missingServer = [];
        $missingFrontend = [];
        $foundVars = [];

        foreach ($this->requiredServerVars as $var) {
            if (! $this->envVarExists($envContent, $var)) {
                $missingServer[] = $var;
            } else {
                $foundVars[$var] = $this->getEnvValue($var);
            }
        }

        foreach ($this->requiredFrontendVars as $var) {
            if (! $this->envVarExists($envContent, $var)) {
                $missingFrontend[] = $var;
            } else {
                $foundVars[$var] = $this->getEnvValue($var);
            }
        }

        $details = $this->verbose ? ['found_variables' => $foundVars] : [];

        if (! empty($missingServer)) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Missing server variables: ' . implode(', ', $missingServer),
                "Add the following to your .env file:\n" . $this->generateEnvSuggestion($missingServer),
                array_merge($details, ['missing_server' => $missingServer])
            );
        }

        if (! empty($missingFrontend)) {
            return DiagnosticResult::warn(
                $this->getName(),
                'Missing frontend variables: ' . implode(', ', $missingFrontend),
                "Add the following to your .env file:\n" . $this->generateFrontendEnvSuggestion($missingFrontend),
                array_merge($details, ['missing_frontend' => $missingFrontend])
            );
        }

        return DiagnosticResult::pass(
            $this->getName(),
            'All required variables present',
            $details
        );
    }

    protected function envVarExists(string $envContent, string $var): bool
    {
        return preg_match('/^' . preg_quote($var, '/') . '=/m', $envContent) === 1;
    }

    protected function getEnvValue(string $var): ?string
    {
        $value = env($var);

        if ($value === null) {
            return null;
        }

        if (in_array($var, ['REVERB_APP_SECRET'], true)) {
            return '********';
        }

        return (string) $value;
    }

    protected function generateEnvSuggestion(array $missingVars): string
    {
        $suggestions = [
            'REVERB_APP_ID' => 'REVERB_APP_ID=my-app-id',
            'REVERB_APP_KEY' => 'REVERB_APP_KEY=my-app-key',
            'REVERB_APP_SECRET' => 'REVERB_APP_SECRET=my-app-secret',
            'REVERB_HOST' => 'REVERB_HOST=localhost',
            'REVERB_PORT' => 'REVERB_PORT=8080',
            'REVERB_SCHEME' => 'REVERB_SCHEME=http',
        ];

        $lines = [];
        foreach ($missingVars as $var) {
            if (isset($suggestions[$var])) {
                $lines[] = $suggestions[$var];
            }
        }

        return implode("\n", $lines);
    }

    protected function generateFrontendEnvSuggestion(array $missingVars): string
    {
        $suggestions = [
            'VITE_REVERB_APP_KEY' => 'VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"',
            'VITE_REVERB_HOST' => 'VITE_REVERB_HOST="${REVERB_HOST}"',
            'VITE_REVERB_PORT' => 'VITE_REVERB_PORT="${REVERB_PORT}"',
            'VITE_REVERB_SCHEME' => 'VITE_REVERB_SCHEME="${REVERB_SCHEME}"',
        ];

        $lines = [];
        foreach ($missingVars as $var) {
            if (isset($suggestions[$var])) {
                $lines[] = $suggestions[$var];
            }
        }

        return implode("\n", $lines);
    }
}
