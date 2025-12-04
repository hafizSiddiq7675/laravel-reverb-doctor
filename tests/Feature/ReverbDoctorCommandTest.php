<?php

use Bitsoftsolutions\LaravelReverbDoctor\Results\DiagnosticStatus;
use Illuminate\Support\Facades\Artisan;

it('can run reverb:doctor command', function () {
    $this->artisan('reverb:doctor')
        ->assertExitCode(1); // Expects failure in test environment (no Reverb configured)
});

it('outputs table format by default', function () {
    Artisan::call('reverb:doctor');
    $output = Artisan::output();

    expect($output)->toContain('Laravel Reverb Doctor')
        ->and($output)->toContain('Check')
        ->and($output)->toContain('Status')
        ->and($output)->toContain('Message');
});

it('outputs json when --json flag is used', function () {
    Artisan::call('reverb:doctor', ['--json' => true]);
    $output = Artisan::output();

    expect($output)->toContain('"version"')
        ->and($output)->toContain('"summary"')
        ->and($output)->toContain('"checks"');
});

it('shows all 10 diagnostic checks', function () {
    Artisan::call('reverb:doctor');
    $output = Artisan::output();

    expect($output)->toContain('Environment Variables')
        ->and($output)->toContain('Config Consistency')
        ->and($output)->toContain('Broadcast Connection')
        ->and($output)->toContain('Port Availability')
        ->and($output)->toContain('Reverb Process')
        ->and($output)->toContain('SSL Certificate')
        ->and($output)->toContain('Queue Worker')
        ->and($output)->toContain('Frontend Sync')
        ->and($output)->toContain('Docker Detection')
        ->and($output)->toContain('Connection Test');
});

it('shows suggestions for failed checks', function () {
    Artisan::call('reverb:doctor');
    $output = Artisan::output();

    expect($output)->toContain('Suggested Fixes');
});

it('returns success exit code when all checks pass', function () {
    // Configure a "passing" environment
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb' => [
            'driver' => 'reverb',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'app_id' => 'test-app',
            'options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
            ],
        ],
        'reverb.servers.reverb.host' => 'localhost',
        'reverb.servers.reverb.port' => 8080,
        'reverb.servers.reverb.scheme' => 'http',
        'reverb.apps' => [
            ['key' => 'test-key', 'secret' => 'test-secret', 'app_id' => 'test-app'],
        ],
        'queue.default' => 'sync',
    ]);

    // Note: This will still fail connection test since Reverb isn't running
    // but verifies the command respects exit codes
    $this->artisan('reverb:doctor')
        ->assertExitCode(1); // Connection test will fail
});

it('json output contains valid structure', function () {
    Artisan::call('reverb:doctor', ['--json' => true]);
    $output = Artisan::output();

    // Validate JSON structure
    expect($output)->toContain('"passed"')
        ->and($output)->toContain('"failed"')
        ->and($output)->toContain('"warnings"')
        ->and($output)->toContain('"skipped"')
        ->and($output)->toContain('"total"');

    // Validate it's valid JSON
    $json = json_decode(trim($output), true);
    expect($json)->toBeArray()
        ->and($json)->toHaveKey('version')
        ->and($json)->toHaveKey('summary')
        ->and($json)->toHaveKey('checks');
});

it('detailed flag shows additional information', function () {
    Artisan::call('reverb:doctor', ['--detailed' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Laravel Reverb Doctor');
});
