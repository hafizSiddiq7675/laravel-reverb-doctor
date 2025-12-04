<?php

declare(strict_types=1);

namespace HmzaUsman\LaravelReverbDoctor\Checks;

use HmzaUsman\LaravelReverbDoctor\Results\DiagnosticResult;

class DockerDetectionCheck extends BaseCheck
{
    public function getName(): string
    {
        return 'Docker Detection';
    }

    public function getDescription(): string
    {
        return 'If Docker/Sail detected, verify port exposure and host bindings';
    }

    public function run(): DiagnosticResult
    {
        $isDocker = $this->isRunningInDocker();
        $isSail = $this->isUsingSail();

        $details = $this->verbose ? [
            'is_docker' => $isDocker,
            'is_sail' => $isSail,
        ] : [];

        // Not running in Docker - skip this check
        if (! $isDocker && ! $isSail) {
            return DiagnosticResult::skip(
                $this->getName(),
                'Not running in Docker',
                $details
            );
        }

        $issues = [];
        $warnings = [];

        $host = env('REVERB_HOST') ?? config('reverb.servers.reverb.host') ?? 'localhost';
        $port = (int) (env('REVERB_PORT') ?? config('reverb.servers.reverb.port') ?? 8080);

        $details['configured_host'] = $host;
        $details['configured_port'] = $port;

        // Check host binding for Docker
        if ($isDocker || $isSail) {
            // In Docker, host should be 0.0.0.0 to accept external connections
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $warnings[] = 'REVERB_HOST is set to localhost - may not be accessible from host machine';
            }

            // Check Sail-specific variables
            if ($isSail) {
                $sailAppPort = env('APP_PORT');
                $sailReverbPort = env('REVERB_PORT');

                $details['SAIL_APP_PORT'] = $sailAppPort;
                $details['SAIL_REVERB_PORT'] = $sailReverbPort;

                // Check if docker-compose.yml exists and has reverb port exposed
                $dockerComposeIssue = $this->checkDockerCompose($port);
                if ($dockerComposeIssue) {
                    $warnings[] = $dockerComposeIssue;
                }
            }
        }

        // Check for common Docker networking issues
        $forwardedHost = env('REVERB_SERVER_HOST');
        if ($forwardedHost) {
            $details['REVERB_SERVER_HOST'] = $forwardedHost;
        }

        if (! empty($issues)) {
            return DiagnosticResult::fail(
                $this->getName(),
                'Docker configuration issues detected',
                implode("\n", $issues),
                $details
            );
        }

        if (! empty($warnings)) {
            return DiagnosticResult::warn(
                $this->getName(),
                'Docker environment detected with potential issues',
                implode("\n", $warnings) . "\n\nEnsure port {$port} is exposed in docker-compose.yml",
                $details
            );
        }

        $environmentType = $isSail ? 'Laravel Sail' : 'Docker';

        return DiagnosticResult::pass(
            $this->getName(),
            "{$environmentType} environment detected",
            $details
        );
    }

    protected function isRunningInDocker(): bool
    {
        // Check for .dockerenv file
        if (file_exists('/.dockerenv')) {
            return true;
        }

        // Check cgroup for docker
        if (file_exists('/proc/1/cgroup')) {
            $cgroup = file_get_contents('/proc/1/cgroup');

            if ($cgroup && (str_contains($cgroup, 'docker') || str_contains($cgroup, 'kubepods'))) {
                return true;
            }
        }

        // Check for DOCKER_* environment variables
        if (getenv('DOCKER_HOST') !== false || getenv('DOCKER_CONTAINER') !== false) {
            return true;
        }

        return false;
    }

    protected function isUsingSail(): bool
    {
        // Check for Sail-specific environment variables
        if (env('SAIL_XDEBUG_MODE') !== null) {
            return true;
        }

        // Check for WWWUSER/WWWGROUP (Sail sets these)
        if (env('WWWUSER') !== null || env('WWWGROUP') !== null) {
            return true;
        }

        // Check if laravel.test or similar Sail container name
        $hostname = gethostname();

        if ($hostname && str_contains($hostname, 'laravel')) {
            return true;
        }

        // Check for docker-compose.yml with sail
        $dockerComposePath = base_path('docker-compose.yml');

        if (file_exists($dockerComposePath)) {
            $content = file_get_contents($dockerComposePath);

            if ($content && str_contains($content, 'sail')) {
                return true;
            }
        }

        return false;
    }

    protected function checkDockerCompose(int $port): ?string
    {
        $dockerComposePath = base_path('docker-compose.yml');

        if (! file_exists($dockerComposePath)) {
            return 'docker-compose.yml not found - ensure Reverb port is exposed';
        }

        $content = file_get_contents($dockerComposePath);

        if ($content === false) {
            return null;
        }

        // Simple check for port exposure (not a full YAML parser)
        $portPattern = "/{$port}:/";

        if (! preg_match($portPattern, $content)) {
            return "Port {$port} may not be exposed in docker-compose.yml. Add to your service's ports section:\n- '\${REVERB_PORT:-{$port}}:{$port}'";
        }

        return null;
    }
}
